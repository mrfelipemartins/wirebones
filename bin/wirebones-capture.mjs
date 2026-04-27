#!/usr/bin/env node

import fs from 'node:fs'
import path from 'node:path'
import { pathToFileURL } from 'node:url'

const inputPath = process.argv[2]

if (!inputPath) {
  console.error('wirebones: missing build input path')
  process.exit(1)
}

const input = JSON.parse(fs.readFileSync(inputPath, 'utf8'))

let chromium
try {
  ;({ chromium } = await import('playwright'))
} catch {
  console.error('wirebones: playwright is not installed. Run: npm install')
  process.exit(1)
}

const browser = await chromium.launch({ headless: !input.headed })
const contextOptions = { ignoreHTTPSErrors: true }

if (input.auth?.storageState) {
  contextOptions.storageState = input.auth.storageState
}

const context = await browser.newContext(contextOptions)

if (Array.isArray(input.auth?.cookies) && input.auth.cookies.length > 0) {
  await context.addCookies(input.auth.cookies)
}

const page = await context.newPage()

if (input.auth?.headers && Object.keys(input.auth.headers).length > 0) {
  await page.setExtraHTTPHeaders(input.auth.headers)
}

try {
  await page.addInitScript(() => {
    window.__WIREBONES_BUILD = true
  })

  fs.mkdirSync(input.outputPath, { recursive: true })
  if (input.capturePath) {
    fs.mkdirSync(path.dirname(input.capturePath), { recursive: true })
  }

  const responses = new Map()
  const groups = groupDefinitions(input.definitions, input)

  for (const group of groups) {
    console.log(`  ${group.route}`)

    for (const definition of group.definitions) {
      responses.set(definition.name, {
        name: definition.name,
        class: definition.class,
        route: String(definition.route || '/'),
        rootTag: 'div',
        breakpoints: {},
        counts: [],
        missing: [],
      })
    }

    for (const width of group.breakpoints) {
      const activeDefinitions = group.definitions.filter((definition) => definition.breakpoints.includes(width))
      const wait = Math.max(0, ...activeDefinitions.map((definition) => definition.wait || 0))

      await page.setViewportSize({ width, height: input.viewportHeight || 900 })

      let redirected = false

      try {
        await page.goto(group.url, { waitUntil: 'networkidle', timeout: 15000 })
      } catch (error) {
        if (!/timeout/i.test(String(error?.message || error))) {
          throw error
        }
      }

      const finalUrl = page.url()
      const requestedPath = new URL(group.url).pathname
      const finalPath = new URL(finalUrl).pathname

      if (finalPath !== requestedPath) {
        redirected = true
        console.log(`    ! ${width}px: redirected from ${requestedPath} to ${finalPath}`)
      }

      if (wait > 0) {
        await page.waitForTimeout(wait)
      }

      if (redirected) {
        continue
      }

      const results = {}

      for (const definition of activeDefinitions) {
        const result = await page.evaluate(snapshotWirebone, {
          name: definition.name,
          config: definition.captureConfig || {},
        })

        if (result) {
          results[definition.name] = result
        }
      }

      for (const definition of activeDefinitions) {
        const responsive = responses.get(definition.name)
        const result = results[definition.name] || null

        if (!result) {
          responsive.missing.push(width)
          continue
        }

        responsive.rootTag = result.rootTag || responsive.rootTag
        responsive.breakpoints[String(width)] = result
        responsive.counts.push(result.bones.length)
      }

      const captured = activeDefinitions.filter((definition) => results[definition.name]).length
      console.log(`    ✓ ${width}px: ${captured}/${activeDefinitions.length} captured`)
    }
  }

  const captures = {}

  for (const definition of input.definitions) {
    const responsive = responses.get(definition.name) || {
      name: definition.name,
      class: definition.class,
      route: String(definition.route || '/'),
      rootTag: 'div',
      breakpoints: {},
      counts: [],
      missing: definition.breakpoints || [],
    }

    console.log(`  ${definition.name}  ${responsive.route}`)

    for (const missing of responsive.missing) {
      console.log(`    - ${missing}px: not found`)
    }

    if (responsive.counts.length > 0) {
      const min = Math.min(...responsive.counts)
      const max = Math.max(...responsive.counts)
      const count = min === max ? `${min} bones` : `${min} → ${max} bones`
      console.log(`    ✓ ${Object.keys(responsive.breakpoints).length} breakpoints, ${count}`)
    }

    if (Object.keys(responsive.breakpoints).length === 0) {
      console.log(`    ! skipped: no captures`)
      continue
    }

    captures[definition.name] = {
      name: responsive.name,
      class: responsive.class,
      route: responsive.route,
      rootTag: responsive.rootTag,
      breakpoints: responsive.breakpoints,
    }
  }

  if (input.capturePath) {
    ensureInside(input.outputPath, input.capturePath)
    fs.writeFileSync(input.capturePath, `${JSON.stringify(captures, null, 2)}\n`)
  }
} finally {
  await browser.close()
}

function buildUrl(baseUrl, route, query, tokenQuery, token) {
  const url = new URL(route.startsWith('http') ? route : `${baseUrl}${route.startsWith('/') ? route : `/${route}`}`)
  url.searchParams.set(query || 'wirebones', '1')
  if (token) url.searchParams.set(tokenQuery || 'wirebones_token', token)
  return url.toString()
}

function groupDefinitions(definitions, input) {
  const groups = new Map()

  for (const definition of definitions) {
    const route = String(definition.route || '/')
    const url = buildUrl(input.baseUrl, route, input.query, input.tokenQuery, input.token)

    if (!groups.has(url)) {
      groups.set(url, {
        route,
        url,
        definitions: [],
        breakpoints: new Set(),
      })
    }

    const group = groups.get(url)
    group.definitions.push(definition)

    for (const width of definition.breakpoints || []) {
      group.breakpoints.add(width)
    }
  }

  return Array.from(groups.values()).map((group) => ({
    ...group,
    breakpoints: Array.from(group.breakpoints).sort((a, b) => a - b),
  }))
}

function safeName(name) {
  return String(name).replace(/[^a-zA-Z0-9._-]/g, '_').replace(/^\.+|\.+$/g, '') || 'wirebone'
}

function ensureInside(root, target) {
  const resolvedRoot = fs.realpathSync(root)
  const resolvedTargetParent = fs.realpathSync(path.dirname(target))

  if (!resolvedTargetParent.startsWith(resolvedRoot)) {
    throw new Error(`wirebones: refusing to write outside output path: ${target}`)
  }
}

function snapshotWirebone(payload) {
  const name = payload.name
  const config = payload.config || {}
  const root = document.querySelector(`[data-wirebone="${CSS.escape(name)}"]`)
  if (!root) return null

  const rootRect = root.getBoundingClientRect()
  const bones = []
  const defaultLeafTags = ['p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'li']
  const leafTags = new Set([...defaultLeafTags, ...(config.leafTags || [])].map((tag) => String(tag).toLowerCase()))
  const excludeTags = new Set((config.excludeTags || []).map((tag) => String(tag).toLowerCase()))
  const excludeSelectors = (config.excludeSelectors || []).map((selector) => String(selector)).filter(Boolean)
  const captureRoundedBorders = config.captureRoundedBorders !== false

  function walk(node) {
    if (!(node instanceof Element)) return
    if (isExcluded(node) || isHidden(node)) return

    const style = getComputedStyle(node)

    const tag = node.tagName.toLowerCase()
    const children = Array.from(node.children).filter((child) => !isExcluded(child) && !isHidden(child))

    const isMedia = ['img', 'svg', 'video', 'canvas', 'picture'].includes(tag)
    const isForm = ['input', 'button', 'textarea', 'select'].includes(tag)
    const isTableStructure = isTableStructuredElement(node)
    const isLeaf = !isTableStructure && (children.length === 0 || isMedia || isForm || leafTags.has(tag))

    const hasBg = !isTransparent(style.backgroundColor)
    const hasBgImage = style.backgroundImage && style.backgroundImage !== 'none'
    const borderWidth = parseFloat(style.borderTopWidth) || 0
    const hasRoundedBorder = captureRoundedBorders
      && borderWidth > 0
      && !isTransparent(style.borderTopColor)
      && parseFloat(style.borderTopLeftRadius) > 0

    if (isLeaf) {
      pushBone(node, style, false)
      return
    }

    if (hasBg || hasBgImage || hasRoundedBorder) {
      pushBone(node, style, true)
    }

    pushTextBones(node, style)

    for (const child of children) walk(child)
  }

  walk(root)

  return {
    name,
    rootTag: root.tagName.toLowerCase(),
    viewportWidth: window.innerWidth,
    width: Math.round(rootRect.width),
    height: Math.round(rootRect.height),
    bones,
  }

  function pushBone(node, style, container) {
    const rect = node.getBoundingClientRect()
    if (rect.width < 1 || rect.height < 1 || rootRect.width < 1) return

    pushRect(rect, radiusFor(node, style), container)
  }

  function pushTextBones(node, style) {
    for (const child of node.childNodes) {
      if (child.nodeType !== Node.TEXT_NODE) continue
      if (!String(child.textContent || '').trim()) continue

      const range = document.createRange()
      range.selectNodeContents(child)

      for (const rect of range.getClientRects()) {
        if (rect.width < 1 || rect.height < 1) continue

        pushRect(rect, Math.min(8, Math.max(2, Math.round(rect.height / 2))), false)
      }

      range.detach()
    }
  }

  function pushRect(rect, radius, container) {
    if (rect.width < 1 || rect.height < 1 || rootRect.width < 1) return

    const x = +(((rect.left - rootRect.left) / rootRect.width) * 100).toFixed(4)
    const y = Math.round(rect.top - rootRect.top)
    const w = +((rect.width / rootRect.width) * 100).toFixed(4)
    const h = Math.round(rect.height)
    const compact = [x, y, w, h, radius]
    if (container) compact.push(true)
    bones.push(compact)
  }

  function isTableStructuredElement(node) {
    const tag = node.tagName.toLowerCase()
    const role = String(node.getAttribute('role') || '').toLowerCase()

    return ['table', 'thead', 'tbody', 'tfoot', 'tr', 'td', 'th'].includes(tag)
      || ['table', 'rowgroup', 'row', 'cell', 'columnheader', 'rowheader'].includes(role)
  }

  function radiusFor(node, style) {
    const tag = node.tagName.toLowerCase()
    const rect = node.getBoundingClientRect()
    const isMedia = ['img', 'svg', 'video', 'canvas', 'picture'].includes(tag)
    const squarish = rect.width > 0 && rect.height > 0 && Math.abs(rect.width - rect.height) < 4

    if (['td', 'th', 'tr', 'table'].includes(tag)) return 0
    if (isMedia && squarish) return '50%'

    return borderRadius(style, rect) ?? 8
  }

  function borderRadius(style, rect) {
    const raw = style.borderRadius
    const squarish = rect.width > 0 && rect.height > 0 && Math.abs(rect.width - rect.height) < 4

    if (raw === '50%') return squarish ? '50%' : 9999

    const tl = parseFloat(style.borderTopLeftRadius) || 0
    const tr = parseFloat(style.borderTopRightRadius) || 0
    const br = parseFloat(style.borderBottomRightRadius) || 0
    const bl = parseFloat(style.borderBottomLeftRadius) || 0
    const max = Math.max(tl, tr, br, bl)

    if (max > 9998) return squarish ? '50%' : 9999
    if (tl === 0 && tr === 0 && br === 0 && bl === 0) return null
    if (tl === tr && tr === br && br === bl) return tl

    return `${tl}px ${tr}px ${br}px ${bl}px`
  }

  function isExcluded(node) {
    if (!(node instanceof Element)) return true
    if (node.hasAttribute('data-wirebone-ignore')) return true
    if (excludeTags.has(node.tagName.toLowerCase())) return true

    for (const selector of excludeSelectors) {
      try {
        if (node.matches(selector)) return true
      } catch {
        // Ignore invalid selectors from user config during capture.
      }
    }

    return false
  }

  function isHidden(node) {
    if (!(node instanceof Element)) return true
    const style = getComputedStyle(node)

    return style.display === 'none' || style.visibility === 'hidden' || style.opacity === '0'
  }

  function isTransparent(value) {
    return !value || value === 'transparent' || value === 'rgba(0, 0, 0, 0)' || value === 'rgb(0 0 0 / 0)'
  }
}

if (import.meta.url === pathToFileURL(process.argv[1]).href) {
  // Keeps Node from tree-shaking the executable in some packaging setups.
}
