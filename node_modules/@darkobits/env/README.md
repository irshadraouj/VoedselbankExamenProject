<p align="center">
  <picture>
    <source
      media="(prefers-color-scheme: dark)"
      srcset="https://github.com/darkobits/env/assets/441546/91ba1796-b274-4d8a-95bc-0b38f414910b"
      width="100%"
    >
    <img
      src="https://github.com/darkobits/env/assets/441546/9fce66f2-a41e-47ef-8b99-7de225f98a31"
      width="100%"
    >
  </picture>
</p>
<p align="center">
  <a
    href="https://www.npmjs.com/package/@darkobits/env"
  ><img
    src="https://img.shields.io/npm/v/@darkobits/env.svg?style=flat-square"
  ></a>
  <a
    href="https://github.com/darkobits/env/actions?query=workflow%3Aci"
  ><img
    src="https://img.shields.io/github/actions/workflow/status/darkobits/env/ci.yml?style=flat-square"
  ></a>
  <a
    href="https://depfu.com/repos/github/darkobits/env"
  ><img
    src="https://img.shields.io/depfu/darkobits/env?style=flat-square"
  ></a>
  <a
    href="https://conventionalcommits.org"
  ><img
    src="https://img.shields.io/static/v1?label=commits&message=conventional&style=flat-square&color=398AFB"
  ></a>
  <a
    href="https://firstdonoharm.dev"
  ><img
    src="https://img.shields.io/static/v1?label=license&message=hippocratic&style=flat-square&color=753065"
  ></a>
</p>

A functional getter/parser for `process.env`.

## Features

- Casts number-like values to numbers.
- Casts `"true"` and `"false"` to booleans.
- Parses JSON.
- Throws if `process` or `process.env` are non-objects.
- (Optional) Throw if a variable is undefined.

## Install

```bash
$ npm i @darkobits/env
```

## Use

This package's default export is a function with the following signature:

```ts
interface Env {
  (variableName: string, strict: boolean = false): any;
  has(variableName: string): boolean;
  eq(variableName: string, value: any, strict: boolean = false): boolean;
}
```

Keeping in mind that the values in `process.env` [may only be strings](https://nodejs.org/api/process.html#process_process_env), let's assume `process.env` looks like this:

```json
{
  "FOO": "foo",
  "BAR": "42",
  "BAZ": "true",
  "QUX": "false",
  "JSON": "{\"kittens\": true}"
}
```

```ts
import env from '@darkobits/env';

env('FOO')         //=> 'foo'
typeof env('FOO')  //=> 'string'

env('BAR')         //=> 42
typeof env('BAR')  //=> 'number'

env('BAZ')         //=> true
typeof env('BAZ')  //=> 'boolean'

env('QUX')         //=> false
typeof env('BAZ')  //=> 'boolean'

env('JSON')        //=> {kittens: true}
typeof env('JSON') //=> 'object'

env('NOAP')        //=> undefined
env('NOAP', true)  //=> throws

// Throws if process.env has been tampered-with.
process.env = null;
env('FOO')         //=> throws

// Throws if process has been tampered-with, or if process doesn't exist.
process = null;
env('FOO')         //=> throws
```

### `env.has`

This helper predicate is a shorthand for `Object.keys(process.env).includes(x)`. It returns `true` if the provided variable name exists in `process.env` and `false` otherwise. Useful when you don't care what the value of a variable is, only whether it is set or not.

Using our example `process.env` object from above:

```ts
env.has('FOO') //=> true
env.has('UNICORNS') //=> false
```

### `env.eq`

This helper predicate is a shorthand for `env(variableName) === value`. It returns `true` if the provided variable name exists in `process.env` and is equal to the provided value and `false` otherwise. Useful when you need to quickly test the value of an environment variable. A third `strict` argument may be set to `true` to cause `env.eq` to throw if the provided variable does not exist in `process.env`.

**Note:** When comparing against non-primitives (objects, arrays), env.eq will serialize the provided `value` and compare it against the serialized (re: string) form of the environment variable.

Using our example `process.env` object from above:

```ts
import env from '@darkobits/env';

env.eq('FOO', 'foo') //=> true
env.eq('BAR', 42)    //=> true
env.eq('BAR', null)  //=> false
env.eq('BAZ', true)  //=> true
env.eq('JSON', {kittens: true}) //=> true
```

<br />
<a href="#top">
  <img src="https://user-images.githubusercontent.com/441546/102322726-5e6d4200-3f34-11eb-89f2-c31624ab7488.png" style="max-width: 100%;">
</a>
