import assert from 'node:assert/strict'
import { EventEmitter } from 'node:events'
import { test } from 'node:test'
import wirebones, {
  buildCommandArgs,
  changedCommandArgs,
  createWirebonesRunner,
  parseChangedOutput,
} from '../../vite/index.js'

test('builds artisan command arguments', () => {
  assert.deepEqual(changedCommandArgs('/tmp/Card.php', { artisan: 'artisan' }), [
    'artisan',
    'wirebones:changed',
    '/tmp/Card.php',
    '--json',
  ])

  assert.deepEqual(buildCommandArgs(['alpha', 'beta'], { artisan: 'artisan', url: 'http://localhost:8000' }), [
    'artisan',
    'wirebones:build',
    'http://localhost:8000',
    '--component=alpha',
    '--component=beta',
  ])
})

test('parses changed command output safely', () => {
  assert.deepEqual(parseChangedOutput('{"components":["one","",2,"two"]}'), ['one', 'two'])
  assert.deepEqual(parseChangedOutput('not-json'), [])
})

test('debounces and batches targeted builds', async () => {
  const calls = []
  const logger = { info() {}, error() {} }

  function fakeSpawn(_command, args) {
    calls.push(args)
    const child = new EventEmitter()
    child.stdout = new EventEmitter()
    child.stderr = new EventEmitter()

    queueMicrotask(() => {
      if (args[1] === 'wirebones:changed') {
        child.stdout.emit('data', JSON.stringify({ components: [args[2].includes('One') ? 'one' : 'two'] }))
      }

      child.emit('close', 0)
    })

    return child
  }

  const runner = createWirebonesRunner({
    artisan: 'artisan',
    debounce: 0,
    logger,
    spawn: fakeSpawn,
  })

  await runner.resolveChanged('/app/One.php')
  await runner.resolveChanged('/app/Two.php')
  await new Promise((resolve) => setTimeout(resolve, 5))
  await runner.flush()

  assert.deepEqual(calls.at(-1), [
    'artisan',
    'wirebones:build',
    '--component=one',
    '--component=two',
  ])
})

test('vite plugin only registers watcher behavior for serve', () => {
  const plugin = wirebones()
  let watched = false
  let changed = false
  const watcher = {
    add() {
      watched = true
    },
    on(event) {
      if (event === 'change') {
        changed = true
      }
    },
  }

  plugin.configResolved({ command: 'build' })
  plugin.configureServer({ config: { root: process.cwd(), logger: console }, watcher })

  assert.equal(watched, false)
  assert.equal(changed, false)
})
