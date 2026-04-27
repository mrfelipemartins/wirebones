import { spawn } from 'node:child_process'
import path from 'node:path'

const DEFAULT_DEBOUNCE = 500

export function changedCommandArgs(file, options = {}) {
  return [
    options.artisan ?? 'artisan',
    'wirebones:changed',
    path.resolve(file),
    '--json',
  ]
}

export function buildCommandArgs(components, options = {}) {
  const args = [
    options.artisan ?? 'artisan',
    'wirebones:build',
  ]

  if (options.url) {
    args.push(options.url)
  }

  for (const component of components) {
    args.push(`--component=${component}`)
  }

  return args
}

export function parseChangedOutput(output) {
  try {
    const parsed = JSON.parse(output)

    return Array.isArray(parsed.components)
      ? parsed.components.filter((component) => typeof component === 'string' && component.length > 0)
      : []
  } catch {
    return []
  }
}

export function createWirebonesRunner(options = {}) {
  const php = options.php ?? 'php'
  const cwd = options.cwd ?? process.cwd()
  const spawnProcess = options.spawn ?? spawn
  const logger = options.logger ?? console
  const pending = new Set()
  const debounce = Math.max(0, Number(options.debounce ?? DEFAULT_DEBOUNCE))
  let timer = null
  let running = false

  function logDebug(message) {
    if (options.debug) {
      logger.info?.(`[wirebones] ${message}`)
    }
  }

  function run(args, stdio = 'pipe') {
    return new Promise((resolve) => {
      const child = spawnProcess(php, args, {
        cwd,
        shell: false,
        stdio,
      })

      let stdout = ''
      let stderr = ''

      child.stdout?.on('data', (chunk) => {
        stdout += chunk.toString()
      })

      child.stderr?.on('data', (chunk) => {
        stderr += chunk.toString()
      })

      child.on('error', (error) => {
        resolve({ code: 1, stdout, stderr: String(error?.message ?? error) })
      })

      child.on('close', (code) => {
        resolve({ code: code ?? 0, stdout, stderr })
      })
    })
  }

  async function resolveChanged(file) {
    const result = await run(changedCommandArgs(file, options))

    if (result.code !== 0) {
      logger.error?.(`[wirebones] failed to inspect changed file: ${file}`)

      if (result.stderr.trim()) {
        logger.error?.(result.stderr.trim())
      }

      return
    }

    const components = parseChangedOutput(result.stdout)

    if (components.length === 0) {
      logDebug(`no Wirebone component affected by ${file}`)

      return
    }

    for (const component of components) {
      pending.add(component)
    }

    schedule()
  }

  function schedule() {
    if (timer) {
      clearTimeout(timer)
    }

    timer = setTimeout(() => {
      timer = null
      void flush()
    }, debounce)
  }

  async function flush() {
    if (running || pending.size === 0) {
      return
    }

    running = true
    const components = Array.from(pending).sort()
    pending.clear()

    logger.info?.(`[wirebones] rebuilding ${components.join(', ')}`)

    const result = await run(buildCommandArgs(components, options), 'inherit')

    if (result.code !== 0) {
      logger.error?.(`[wirebones] build failed with exit code ${result.code}`)
    } else {
      logger.info?.('[wirebones] build complete')
    }

    running = false

    if (pending.size > 0) {
      await flush()
    }
  }

  return {
    resolveChanged,
    flush,
    pending,
  }
}

export default function wirebones(options = {}) {
  let command = 'serve'

  return {
    name: 'wirebones',
    apply: 'serve',
    configResolved(config) {
      command = config.command
    },
    configureServer(server) {
      if (command !== 'serve') {
        return
      }

      const runner = createWirebonesRunner({
        ...options,
        cwd: options.cwd ?? server.config.root,
        logger: server.config.logger,
      })

      server.watcher.add(['**/*.php', '**/*.blade.php'])

      server.watcher.on('change', (file) => {
        if (!file.endsWith('.php') && !file.endsWith('.blade.php')) {
          return
        }

        void runner.resolveChanged(file)
      })
    },
  }
}
