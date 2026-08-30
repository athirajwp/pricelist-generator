import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

if (process.env.VERCEL === '1' || process.env.VERCEL === 'true') {
  console.log('Running on Vercel: skipping postbuild step.');
  process.exit(0);
}

const source = path.join(__dirname, '../backend/public/build/index.html');
const dest = path.join(__dirname, '../backend/resources/views/react.blade.php');

try {
  if (fs.existsSync(source)) {
    fs.copyFileSync(source, dest);
    console.log('Successfully copied index.html to react.blade.php');
  } else {
    console.error('Source index.html not found at: ' + source);
  }
} catch (e) {
  console.error('Error copying file:', e);
}
