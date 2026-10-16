/**
 * Migration script: Converts all page links to clean URLs without .html
 * (e.g. /about, /fees, /admissions, /contact, /teachers, /gallery, /library, /anthem, /)
 * and deploys to root and frontend/ with alias files for both case variations.
 */
const fs = require('fs');
const path = require('path');

const ROOT_DIR = path.resolve(__dirname, '..');
const FRONTEND_DIR = path.join(ROOT_DIR, 'frontend');

// Canonical page definitions
const PAGES = [
    {
        srcFile: 'index.html',
        cleanPath: '/',
        rootAliases: ['index.html', 'index-redesign.html'],
        canonical: 'https://stlawrencejuniorschoolkabowa.com/'
    },
    {
        srcFile: 'About.html',
        cleanPath: '/about',
        rootAliases: ['About.html', 'about.html', 'About-redesign.html'],
        canonical: 'https://stlawrencejuniorschoolkabowa.com/about'
    },
    {
        srcFile: 'Admission.html',
        cleanPath: '/admissions',
        rootAliases: ['Admission.html', 'admission.html', 'admissions.html', 'Admission-redesign.html'],
        canonical: 'https://stlawrencejuniorschoolkabowa.com/admissions'
    },
    {
        srcFile: 'Fees.html',
        cleanPath: '/fees',
        rootAliases: ['Fees.html', 'fees.html'],
        canonical: 'https://stlawrencejuniorschoolkabowa.com/fees'
    },
    {
        srcFile: 'Contact.html',
        cleanPath: '/contact',
        rootAliases: ['Contact.html', 'contact.html', 'Contact-redesign.html'],
        canonical: 'https://stlawrencejuniorschoolkabowa.com/contact'
    },
    {
        srcFile: 'Teachers.html',
        cleanPath: '/teachers',
        rootAliases: ['Teachers.html', 'teachers.html', 'Teachers-redesign.html'],
        canonical: 'https://stlawrencejuniorschoolkabowa.com/teachers'
    },
    {
        srcFile: 'Gallery.html',
        cleanPath: '/gallery',
        rootAliases: ['Gallery.html', 'gallery.html', 'Gallery-redesign.html'],
        canonical: 'https://stlawrencejuniorschoolkabowa.com/gallery'
    },
    {
        srcFile: 'Library.html',
        cleanPath: '/library',
        rootAliases: ['Library.html', 'library.html', 'Library-redesign.html'],
        canonical: 'https://stlawrencejuniorschoolkabowa.com/library'
    },
    {
        srcFile: 'School-Anthem.html',
        cleanPath: '/anthem',
        rootAliases: ['School-Anthem.html', 'Anthem.html', 'anthem.html', 'school-anthem.html'],
        canonical: 'https://stlawrencejuniorschoolkabowa.com/anthem'
    },
    {
        srcFile: 'Teacher-Profile.html',
        cleanPath: '/teacher-profile',
        rootAliases: ['Teacher-Profile.html', 'teacher-profile.html'],
        canonical: 'https://stlawrencejuniorschoolkabowa.com/teacher-profile'
    }
];

function replaceAllWithCleanUrls(content) {
    let result = content;

    // 1. Replace all legacy redesign links first
    result = result.replace(/About-redesign\.html/gi, 'About.html');
    result = result.replace(/Admission-redesign\.html/gi, 'Admission.html');
    result = result.replace(/Contact-redesign\.html/gi, 'Contact.html');
    result = result.replace(/Gallery-redesign\.html/gi, 'Gallery.html');
    result = result.replace(/Library-redesign\.html/gi, 'Library.html');
    result = result.replace(/Teachers-redesign\.html/gi, 'Teachers.html');
    result = result.replace(/index-redesign\.html/gi, 'index.html');

    // 2. Replace hrefs with clean paths (no .html)
    result = result.replace(/href=["'](?:\.\.\/|\.\/|\/)?About\.html(["'#])/gi, 'href="/about$1');
    result = result.replace(/href=["'](?:\.\.\/|\.\/|\/)?Fees\.html(["'#])/gi, 'href="/fees$1');
    result = result.replace(/href=["'](?:\.\.\/|\.\/|\/)?Admission\.html(["'#])/gi, 'href="/admissions$1');
    result = result.replace(/href=["'](?:\.\.\/|\.\/|\/)?Contact\.html(["'#])/gi, 'href="/contact$1');
    result = result.replace(/href=["'](?:\.\.\/|\.\/|\/)?Teachers\.html(["'#])/gi, 'href="/teachers$1');
    result = result.replace(/href=["'](?:\.\.\/|\.\/|\/)?Gallery\.html(["'#])/gi, 'href="/gallery$1');
    result = result.replace(/href=["'](?:\.\.\/|\.\/|\/)?Library\.html(["'#])/gi, 'href="/library$1');
    result = result.replace(/href=["'](?:\.\.\/|\.\/|\/)?School-Anthem\.html(["'#])/gi, 'href="/anthem$1');
    result = result.replace(/href=["'](?:\.\.\/|\.\/|\/)?Anthem\.html(["'#])/gi, 'href="/anthem$1');
    result = result.replace(/href=["'](?:\.\.\/|\.\/|\/)?Teacher-Profile\.html(["'#])/gi, 'href="/teacher-profile$1');
    result = result.replace(/href=["'](?:\.\.\/|\.\/|\/)?index\.html(["'#])/gi, 'href="/$1');

    return result;
}

function transformForRoot(content, page) {
    let result = replaceAllWithCleanUrls(content);

    // Strip any PRODUCTION_CLEAN_URL_REDIRECT block from root files
    const markerStart = '<!-- PRODUCTION_CLEAN_URL_REDIRECT_START -->';
    const markerEnd = '<!-- PRODUCTION_CLEAN_URL_REDIRECT_END -->';
    if (result.includes(markerStart)) {
        const regex = new RegExp(`${markerStart}[\\s\\S]*?${markerEnd}\\n?`, 'g');
        result = result.replace(regex, '');
    }

    // Normalize asset paths: ../css/ -> css/, ../js/ -> js/, etc.
    result = result.replace(/(\.\.\/)+css\//g, 'css/');
    result = result.replace(/(\.\.\/)+js\//g, 'js/');
    result = result.replace(/(\.\.\/)+img\//g, 'img/');
    result = result.replace(/(\.\.\/)+audio\//g, 'audio/');
    result = result.replace(/(\.\.\/)+backend\//g, 'backend/');
    result = result.replace(/(\.\.\/)+sitemap\.xml/g, 'sitemap.xml');

    // Update canonical
    if (page.canonical) {
        result = result.replace(/<link\s+rel=["']canonical["']\s+href=["'][^"']*["']\s*\/?>/i,
            `<link rel="canonical" href="${page.canonical}">`);
    }

    return result;
}

function createRedirectHTML(targetUrl) {
    return `<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="refresh" content="0; url=${targetUrl}">
    <title>Redirecting...</title>
    <script>
        (function() {
            var host = window.location.hostname;
            var isLocal = host === 'localhost' || host === '127.0.0.1' || host.endsWith('.local');
            if (!isLocal) {
                window.location.replace('${targetUrl}' + window.location.search + window.location.hash);
            }
        })();
    </script>
</head>
<body>
    <p>Redirecting to <a href="${targetUrl}">${targetUrl}</a>...</p>
</body>
</html>
`;
}

console.log('--- Applying Clean URLs Across All Pages ---');

PAGES.forEach(page => {
    let srcPath = path.join(FRONTEND_DIR, page.srcFile);
    if (!fs.existsSync(srcPath)) {
        srcPath = path.join(ROOT_DIR, page.srcFile);
    }

    if (!fs.existsSync(srcPath)) {
        console.error(`Source not found: ${page.srcFile}`);
        return;
    }

    let raw = fs.readFileSync(srcPath, 'utf8');

    // 1. Update frontend/ version
    let frontendClean = replaceAllWithCleanUrls(raw);
    const frontendDst = path.join(FRONTEND_DIR, page.srcFile);
    fs.writeFileSync(frontendDst, frontendClean, 'utf8');
    console.log(`[Frontend] Updated clean links in frontend/${page.srcFile}`);

    // 2. Generate root page content
    const rootClean = transformForRoot(frontendClean, page);

    // Save to all root aliases
    page.rootAliases.forEach(alias => {
        const rootPath = path.join(ROOT_DIR, alias);
        // If it's a legacy redesign alias, make it a redirect
        if (alias.toLowerCase().includes('-redesign')) {
            fs.writeFileSync(rootPath, createRedirectHTML(page.cleanPath), 'utf8');
            console.log(`[Root] Created legacy redirect: ${alias} -> ${page.cleanPath}`);
        } else {
            fs.writeFileSync(rootPath, rootClean, 'utf8');
            console.log(`[Root] Generated page: ${alias}`);
        }
    });
});

console.log('--- Finished Applying Clean URLs ---');
