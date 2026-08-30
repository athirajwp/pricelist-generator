const fs = require('fs');
const https = require('https');
const path = require('path');

const phpUrls = [
    'https://windows.php.net/downloads/releases/archives/php-8.2.31-nts-Win32-vs16-x64.zip',
    'https://windows.php.net/downloads/releases/archives/php-8.2.27-nts-Win32-vs16-x64.zip',
    'https://windows.php.net/downloads/releases/php-8.2.27-nts-Win32-vs16-x64.zip',
    'https://windows.php.net/downloads/releases/archives/php-8.2.20-nts-Win32-vs16-x64.zip'
];
const composerUrl = 'https://getcomposer.org/composer.phar';

const toolsDir = path.join(__dirname, '.tools');
const phpZip = path.join(toolsDir, 'php.zip');
const composerPhar = path.join(toolsDir, 'composer.phar');

if (!fs.existsSync(toolsDir)) {
    fs.mkdirSync(toolsDir, { recursive: true });
}

function downloadFile(url, dest) {
    return new Promise((resolve, reject) => {
        console.log(`Starting download from ${url} to ${dest}...`);
        const file = fs.createWriteStream(dest);
        
        const request = https.get(url, (response) => {
            if (response.statusCode === 301 || response.statusCode === 302) {
                downloadFile(response.headers.location, dest).then(resolve).catch(reject);
                return;
            }
            
            if (response.statusCode !== 200) {
                file.close();
                fs.unlink(dest, () => {});
                reject(new Error(`Failed to download: Status Code ${response.statusCode}`));
                return;
            }
            
            const totalSize = parseInt(response.headers['content-length'], 10);
            let downloadedSize = 0;
            let lastPercent = 0;

            response.on('data', (chunk) => {
                downloadedSize += chunk.length;
                if (totalSize) {
                    const percent = Math.floor((downloadedSize / totalSize) * 100);
                    if (percent >= lastPercent + 10) {
                        lastPercent = percent;
                        console.log(`Progress: ${percent}% (${(downloadedSize / 1024 / 1024).toFixed(2)}MB / ${(totalSize / 1024 / 1024).toFixed(2)}MB)`);
                    }
                }
            });

            response.pipe(file);
            
            file.on('finish', () => {
                file.close();
                console.log(`Finished downloading ${path.basename(dest)}!`);
                resolve();
            });
        });

        request.on('error', (err) => {
            fs.unlink(dest, () => {});
            reject(err);
        });
    });
}

async function downloadPhp() {
    let success = false;
    for (const url of phpUrls) {
        try {
            console.log(`Attempting PHP download from: ${url}`);
            await downloadFile(url, phpZip);
            success = true;
            break;
        } catch (e) {
            console.warn(`URL failed: ${url} (${e.message})`);
        }
    }
    if (!success) {
        throw new Error('All PHP download URLs failed.');
    }
}

async function run() {
    try {
        const type = process.argv[2];
        if (type === 'php') {
            await downloadPhp();
        } else if (type === 'composer') {
            await downloadFile(composerUrl, composerPhar);
        } else {
            await downloadPhp();
            await downloadFile(composerUrl, composerPhar);
        }
        process.exit(0);
    } catch (err) {
        console.error(`Download failed: ${err.message}`);
        process.exit(1);
    }
}

run();
