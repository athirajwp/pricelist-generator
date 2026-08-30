const puppeteer = require('puppeteer');
const fs = require('fs');
const path = require('path');

function parseArgs() {
  const args = process.argv.slice(2);
  const options = {
    url: 'http://127.0.0.1:9000/price-list',
    output: path.join(__dirname, '../storage/app/public/price_list.pdf'),
    delay: 1000,
  };

  for (let i = 0; i < args.length; i++) {
    if (args[i] === '--url' && args[i + 1]) {
      options.url = args[i + 1];
      i++;
    } else if (args[i] === '--output' && args[i + 1]) {
      options.output = args[i + 1];
      i++;
    } else if (args[i] === '--delay' && args[i + 1]) {
      options.delay = parseInt(args[i + 1], 10);
      i++;
    }
  }
  return options;
}

async function run() {
  const { url, output, delay } = parseArgs();
  console.log(`[Puppeteer] Generating Vector PDF for ${url}...`);

  const outputDir = path.dirname(output);
  if (!fs.existsSync(outputDir)) {
    fs.mkdirSync(outputDir, { recursive: true });
  }

  const browser = await puppeteer.launch({
    headless: 'new',
    args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-dev-shm-usage'],
  });

  try {
    const page = await browser.newPage();
    // Use exact A4 portrait viewport dimensions (794px x 1123px at 96 DPI) so screen media queries remain single column
    await page.setViewport({ width: 794, height: 1123, deviceScaleFactor: 2 });

    await page.goto(url, { waitUntil: 'load', timeout: 15000 });

    if (delay > 0) {
      await new Promise((resolve) => setTimeout(resolve, delay));
    }

    await page.waitForSelector('#price-list-document', { timeout: 10000 }).catch(() => {});

    // Emulate print media
    await page.emulateMediaType('print');

    // Inject strict single-column A4 print styles overriding screen flex-row rules
    await page.addStyleTag({
      content: `
        @page {
          size: A4 portrait !important;
          margin: 0mm !important;
        }
        html, body {
          background: #ffffff !important;
          margin: 0 !important;
          padding: 0 !important;
          width: 210mm !important;
        }
        header, footer, nav, button, .no-print, [role="dialog"] {
          display: none !important;
        }
        #price-list-document {
          display: block !important;
          width: 210mm !important;
          max-width: 210mm !important;
          margin: 0 auto !important;
          padding: 0 !important;
          gap: 0 !important;
          flex-direction: column !important;
        }
        #price-list-document > div {
          width: 210mm !important;
          max-width: 210mm !important;
          margin: 0 auto !important;
        }
        .a4-page-sheet {
          width: 210mm !important;
          min-height: 297mm !important;
          box-shadow: none !important;
          border-radius: 0 !important;
          margin: 0 auto !important;
          page-break-after: always !important;
          break-after: page !important;
        }
      `
    });

    await page.pdf({
      path: output,
      format: 'A4',
      printBackground: true,
      preferCSSPageSize: true,
      margin: {
        top: '0mm',
        right: '0mm',
        bottom: '0mm',
        left: '0mm',
      },
    });

    console.log(`[Puppeteer SUCCESS] Vector A4 PDF saved to ${output}`);
  } catch (err) {
    console.error(`[Puppeteer Error] ${err.message}`);
    process.exit(1);
  } finally {
    await browser.close();
  }
}

run();
