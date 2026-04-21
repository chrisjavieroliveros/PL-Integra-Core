import fs from 'node:fs';
import path from 'node:path';
import { spawnSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const rootDir = path.resolve(__dirname, '..');
const pluginMainFile = path.join(rootDir, 'integra-core.php');
const packageJsonFile = path.join(rootDir, 'package.json');
const distDir = path.join(rootDir, 'dist');
const stageRootDir = path.join(distDir, 'PL-Integra-Core');
const includePaths = [
  'assets',
  'includes',
  'library',
  'integra-core.php'
];
const sassEntrypoints = [
  {
    source: path.join(rootDir, 'scss', 'integra-core.scss'),
    output: path.join(rootDir, 'assets', 'css', 'integra-core.min.css')
  }
];

function fail(message) {
  console.error(message);
  process.exit(1);
}

function run(command, args, options = {}) {
  const result = spawnSync(command, args, {
    cwd: rootDir,
    stdio: 'inherit',
    ...options
  });

  if (result.status !== 0) {
    process.exit(result.status ?? 1);
  }
}

function readJson(filePath) {
  return JSON.parse(fs.readFileSync(filePath, 'utf8'));
}

function writeJson(filePath, value) {
  fs.writeFileSync(filePath, `${JSON.stringify(value, null, 2)}\n`);
}

function bumpPatchVersion(version) {
  const match = /^(\d+)\.(\d+)\.(\d+)$/.exec(version);

  if (!match) {
    fail(`Unsupported version format "${version}". Expected x.y.z.`);
  }

  const major = Number.parseInt(match[1], 10);
  const minor = Number.parseInt(match[2], 10);
  const patch = Number.parseInt(match[3], 10) + 1;

  return `${major}.${minor}.${patch}`;
}

function updatePluginVersion(filePath, nextVersion) {
  const source = fs.readFileSync(filePath, 'utf8');
  const nextSource = source
    .replace(/^(\s*\*\s*Version:\s*)(.+)$/m, `$1${nextVersion}`)
    .replace(
      /^define\(\s*'INTEGRA_CORE_VERSION',\s*'[^']+'\s*\);$/m,
      `define( 'INTEGRA_CORE_VERSION', '${nextVersion}' );`
    );

  if (source === nextSource) {
    fail(`Failed to update plugin version in ${path.basename(filePath)}.`);
  }

  fs.writeFileSync(filePath, nextSource);
}

function getSassCommand() {
  const candidates = [
    path.join(rootDir, 'node_modules', '.bin', 'sass'),
    path.join(rootDir, 'node_modules', 'sass', 'sass.js')
  ];

  for (const candidate of candidates) {
    if (fs.existsSync(candidate)) {
      if (candidate.endsWith('.js')) {
        return {
          command: process.execPath,
          argsPrefix: [candidate]
        };
      }

      return {
        command: candidate,
        argsPrefix: []
      };
    }
  }

  fail('Missing Sass dependency. Run "npm install" first.');
}

function buildStyles() {
  const sass = getSassCommand();

  for (const entry of sassEntrypoints) {
    run(sass.command, [
      ...sass.argsPrefix,
      '--style=compressed',
      '--no-source-map',
      entry.source,
      entry.output
    ]);
  }
}

function resetDir(directory) {
  fs.rmSync(directory, { recursive: true, force: true });
  fs.mkdirSync(directory, { recursive: true });
}

function copyForPackage() {
  resetDir(stageRootDir);

  for (const relativePath of includePaths) {
    const sourcePath = path.join(rootDir, relativePath);
    const destinationPath = path.join(stageRootDir, relativePath);

    fs.cpSync(sourcePath, destinationPath, { recursive: true });
  }
}

function createArchive(version) {
  fs.mkdirSync(distDir, { recursive: true });

  const archiveName = `PL-Integra-Core-${version}.zip`;
  const archivePath = path.join(distDir, archiveName);

  fs.rmSync(archivePath, { force: true });

  run('zip', ['-rq', archiveName, path.basename(stageRootDir)], { cwd: distDir });

  return archivePath;
}

function main() {
  const packageJson = readJson(packageJsonFile);
  const currentVersion = packageJson.version;
  const nextVersion = bumpPatchVersion(currentVersion);

  buildStyles();
  updatePluginVersion(pluginMainFile, nextVersion);

  packageJson.version = nextVersion;
  writeJson(packageJsonFile, packageJson);

  copyForPackage();

  const archivePath = createArchive(nextVersion);

  console.log(`Built plugin version ${nextVersion}`);
  console.log(`Package: ${archivePath}`);
}

main();
