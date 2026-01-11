const fs = require('fs');
const path = require('path');
const puppeteer = require('puppeteer');
const marked = require('marked');
const grayMatter = require('gray-matter');

// Configurazione
const INPUT_FILE = process.argv[2] || 'Documentazione/Commerciale/PORTFOLIO_PRESENTATION.md';
const OUTPUT_FILE = process.argv[3] || INPUT_FILE.replace('.md', '.pdf');

// CSS Stili per il PDF
const STYLES = `
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&family=JetBrains+Mono:wght@400;500&display=swap');
    
    body {
        font-family: 'Inter', sans-serif;
        font-size: 14px;
        line-height: 1.6;
        color: #1f2937;
        margin: 0;
        padding: 40px;
        max-width: 100%;
    }
    
    h1, h2, h3, h4, h5, h6 {
        font-family: 'Inter', sans-serif;
        color: #111827;
        margin-top: 2rem;
        margin-bottom: 1rem;
    }
    
    h1 { font-size: 32px; border-bottom: 2px solid #e5e7eb; padding-bottom: 10px; color: #2563eb; }
    h2 { font-size: 24px; border-bottom: 1px solid #e5e7eb; padding-bottom: 8px; margin-top: 3rem; }
    h3 { font-size: 18px; color: #4b5563; }
    
    code {
        font-family: 'JetBrains Mono', monospace;
        background-color: #f3f4f6;
        padding: 2px 4px;
        border-radius: 4px;
        font-size: 0.9em;
    }
    
    pre {
        background-color: #1f2937;
        color: #f9fafb;
        padding: 15px;
        border-radius: 8px;
        overflow-x: auto;
        font-family: 'JetBrains Mono', monospace;
    }
    
    blockquote {
        background: #f1f5f9;
        border-left: 4px solid #3b82f6;
        margin: 1.5em 0;
        padding: 1em;
        font-style: italic;
    }
    
    table {
        border-collapse: collapse;
        width: 100%;
        margin: 20px 0;
        font-size: 13px;
    }
    
    th, td {
        border: 1px solid #e5e7eb;
        padding: 8px 12px;
        text-align: left;
    }
    
    th {
        background-color: #f8fafc;
        font-weight: 600;
        color: #334155;
    }
    
    tr:nth-child(even) {
        background-color: #f9fafb;
    }
    
    /* Gestione Page Breaks */
    .page-break {
        page-break-after: always;
    }
    
    hr {
        border: 0;
        height: 1px;
        background: #e5e7eb;
        margin: 2rem 0;
    }
    
    /* Alert styles mimicking GitHub alerts */
    .alert {
        padding: 1rem;
        border-radius: 6px;
        margin-bottom: 1rem;
        border-left: 4px solid;
    }
    .alert-note { background-color: #f0f9ff; border-color: #0ea5e9; }
    .alert-tip { background-color: #f0fdf4; border-color: #22c55e; }
    .alert-important { background-color: #f5f3ff; border-color: #8b5cf6; }
    .alert-warning { background-color: #fffbeb; border-color: #f59e0b; }
    .alert-caution { background-color: #fef2f2; border-color: #ef4444; }
    
    /* Header/Footer helpers */
    .header-content, .footer-content {
        font-size: 10px;
        color: #9ca3af;
        width: 100%;
        display: flex;
        justify-content: space-between;
    }
`;

async function convert() {
    console.log(`🚀 Avvio generazione PDF...`);
    console.log(`📄 Input: ${INPUT_FILE}`);

    if (!fs.existsSync(INPUT_FILE)) {
        console.error(`❌ Errore: File ${INPUT_FILE} non trovato.`);
        process.exit(1);
    }

    const fileContent = fs.readFileSync(INPUT_FILE, 'utf-8');

    // Parse Front Matter (se presente)
    const { content: markdownContent, data: frontMatter } = grayMatter(fileContent);

    // Configura Marked
    marked.setOptions({
        gfm: true,
        breaks: true
    });

    // Converti Markdown in HTML
    let htmlContent = marked.parse(markdownContent);

    // Gestisci manualmente i page-break per div specifici usati nel markdown originale
    htmlContent = htmlContent.replace(/<div style="page-break-after: always;"><\/div>/g, '<div class="page-break"></div>');

    // Wrapper HTML completo
    const html = `
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>${STYLES}</style>
        </head>
        <body>
            ${htmlContent}
        </body>
        </html>
    `;

    // Avvia Puppeteer
    const browser = await puppeteer.launch({ headless: 'new' });
    const page = await browser.newPage();

    // Set content and wait for network idle (fonts loading etc)
    await page.setContent(html, { waitUntil: 'networkidle0' });

    // Genera PDF
    await page.pdf({
        path: OUTPUT_FILE,
        format: 'A4',
        printBackground: true,
        margin: {
            top: '20mm',
            bottom: '20mm',
            left: '20mm',
            right: '20mm'
        },
        displayHeaderFooter: true,
        headerTemplate: '<div class="header-content" style="padding-left: 20mm; padding-right: 20mm;"><span>Fratellanza Militare - Archivio Gestionale</span></div>',
        footerTemplate: '<div class="footer-content" style="padding-left: 20mm; padding-right: 20mm;"><span>© 2026 Enterprise Edition</span><span class="pageNumber"></span></div>'
    });

    await browser.close();
    console.log(`✅ PDF Generato con successo: ${OUTPUT_FILE}`);
}

convert().catch(err => {
    console.error('❌ Errore critico:', err);
    process.exit(1);
});
