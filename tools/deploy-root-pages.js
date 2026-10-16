/**
 * Script to deploy clean root pages from frontend/
 * Generates root index.html and root subpages with normalized asset paths.
 * Adds production redirects in frontend/*.html so visitors with cached /frontend/ URLs
 * are seamlessly updated to clean URLs (e.g. stlawrencejuniorschoolkabowa.com/).
 */
const fs = require('fs');
const path = require('path');

const ROOT_DIR = path.resolve(__dirname, '..');
const FRONTEND_DIR = path.join(ROOT_DIR, 'frontend');

const PAGES = [
    {
        src: 'index-redesign.html',
        rootDst: ['index.html', 'index-redesign.html'],
        cleanPath: '/',
        canonical: 'https://stlawrencejuniorschoolkabowa.com/'
    },
    {
        src: 'About-redesign.html',
        rootDst: ['About-redesign.html', 'about.html'],
        cleanPath: '/About-redesign.html',
        canonical: 'https://stlawrencejuniorschoolkabowa.com/about'
    },
    {
        src: 'Fees.html',
        rootDst: ['Fees.html', 'fees.html'],
        cleanPath: '/Fees.html',
        canonical: 'https://stlawrencejuniorschoolkabowa.com/fees'
    },
    {
        src: 'Admission-redesign.html',
        rootDst: ['Admission-redesign.html', 'admission.html', 'admissions.html'],
        cleanPath: '/Admission-redesign.html',
        canonical: 'https://stlawrencejuniorschoolkabowa.com/admissions'
    },
    {
        src: 'Contact-redesign.html',
        rootDst: ['Contact-redesign.html', 'contact.html'],
        cleanPath: '/Contact-redesign.html',
        canonical: 'https://stlawrencejuniorschoolkabowa.com/contact'
    },
    {
        src: 'Teachers-redesign.html',
        rootDst: ['Teachers-redesign.html', 'teachers.html'],
        cleanPath: '/Teachers-redesign.html',
        canonical: 'https://stlawrencejuniorschoolkabowa.com/teachers'
    },
    {
        src: 'Gallery-redesign.html',
        rootDst: ['Gallery-redesign.html', 'gallery.html'],
        cleanPath: '/Gallery-redesign.html',
        canonical: 'https://stlawrencejuniorschoolkabowa.com/gallery'
    },
    {
        src: 'Library-redesign.html',
        rootDst: ['Library-redesign.html', 'library.html'],
        cleanPath: '/Library-redesign.html',
        canonical: 'https://stlawrencejuniorschoolkabowa.com/library'
    },
    {
        src: 'School-Anthem.html',
        rootDst: ['School-Anthem.html', 'anthem.html', 'school-anthem.html'],
        cleanPath: '/School-Anthem.html',
        canonical: 'https://stlawrencejuniorschoolkabowa.com/anthem'
    },
    {
        src: 'Teacher-Profile.html',
        rootDst: ['Teacher-Profile.html', 'teacher-profile.html'],
        cleanPath: '/Teacher-Profile.html',
        canonical: 'https://stlawrencejuniorschoolkabowa.com/teacher-profile'
    }
];

function transformForRoot(content, page) {
    let result = content;

    // Normalize asset paths: ../css/ -> css/, ../js/ -> js/, etc.
    result = result.replace(/(\.\.\/)+css\//g, 'css/');
    result = result.replace(/(\.\.\/)+js\//g, 'js/');
    result = result.replace(/(\.\.\/)+img\//g, 'img/');
    result = result.replace(/(\.\.\/)+audio\//g, 'audio/');
    result = result.replace(/(\.\.\/)+backend\//g, 'backend/');
    result = result.replace(/(\.\.\/)+sitemap\.xml/g, 'sitemap.xml');

    // Normalize home navigation links
    result = result.replace(/href=["'](\.\.\/)*index-redesign\.html["']/g, 'href="/"');
    result = result.replace(/href=["']index-redesign\.html["']/g, 'href="/"');

    // Update canonical if needed
    if (page.canonical) {
        result = result.replace(/<link\s+rel=["']canonical["']\s+href=["'][^"']*["']\s*\/?>/i,
            `<link rel="canonical" href="${page.canonical}">`);
    }

    return result;
}

function addProductionRedirect(content, cleanPath) {
    // If redirect script is already added, remove or skip
    const markerStart = '<!-- PRODUCTION_CLEAN_URL_REDIRECT_START -->';
    const markerEnd = '<!-- PRODUCTION_CLEAN_URL_REDIRECT_END -->';

    let cleaned = content;
    if (cleaned.includes(markerStart)) {
        const regex = new RegExp(`${markerStart}[\\s\\S]*?${markerEnd}\\n?`, 'g');
        cleaned = cleaned.replace(regex, '');
    }

    const redirectSnippet = `${markerStart}
    <script>
        (function() {
            var host = window.location.hostname;
            var isLocal = host === 'localhost' || host === '127.0.0.1' || host.endsWith('.local');
            if (!isLocal) {
                window.location.replace('${cleanPath}' + window.location.search + window.location.hash);
            }
        })();
    </script>
    <noscript><meta http-equiv="refresh" content="0; url=${cleanPath}"></noscript>
    ${markerEnd}`;

    // Insert right after <head>
    return cleaned.replace(/<head(\s[^>]*)?>/i, `$&\n    ${redirectSnippet}`);
}

console.log('Processing pages...');

PAGES.forEach(page => {
    const srcPath = path.join(FRONTEND_DIR, page.src);
    if (!fs.existsSync(srcPath)) {
        console.warn(`File not found: ${srcPath}`);
        return;
    }

    const rawContent = fs.readFileSync(srcPath, 'utf8');

    // 1. Transform for Root and write to all rootDst destinations
    const rootContent = transformForRoot(rawContent, page);
    page.rootDst.forEach(dstName => {
        const dstPath = path.join(ROOT_DIR, dstName);
        fs.writeFileSync(dstPath, rootContent, 'utf8');
        console.log(`[Root] Generated: ${dstName}`);
    });

    // 2. Add production redirect in frontend file
    const redirectedContent = addProductionRedirect(rawContent, page.cleanPath);
    fs.writeFileSync(srcPath, redirectedContent, 'utf8');
    console.log(`[Frontend] Updated with clean URL redirect: frontend/${page.src} -> ${page.cleanPath}`);
});

console.log('Done! All clean URLs deployed.');
