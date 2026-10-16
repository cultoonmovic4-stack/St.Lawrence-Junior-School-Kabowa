/**
 * Migration script: Converts all -redesign page names to standard normal names
 * (e.g. About.html, Admission.html, Contact.html, Teachers.html, Gallery.html, Library.html).
 * Updates internal href links across all pages and deploys to root and frontend/.
 */
const fs = require('fs');
const path = require('path');

const ROOT_DIR = path.resolve(__dirname, '..');
const FRONTEND_DIR = path.join(ROOT_DIR, 'frontend');

// Canonical page definitions
const PAGES = [
    {
        oldSrc: 'index-redesign.html',
        cleanName: 'index.html',
        rootAliases: ['index.html', 'index-redesign.html'],
        canonical: 'https://stlawrencejuniorschoolkabowa.com/'
    },
    {
        oldSrc: 'About-redesign.html',
        cleanName: 'About.html',
        rootAliases: ['About.html', 'about.html', 'About-redesign.html'],
        canonical: 'https://stlawrencejuniorschoolkabowa.com/About.html'
    },
    {
        oldSrc: 'Admission-redesign.html',
        cleanName: 'Admission.html',
        rootAliases: ['Admission.html', 'admission.html', 'admissions.html', 'Admission-redesign.html'],
        canonical: 'https://stlawrencejuniorschoolkabowa.com/Admission.html'
    },
    {
        oldSrc: 'Fees.html',
        cleanName: 'Fees.html',
        rootAliases: ['Fees.html', 'fees.html'],
        canonical: 'https://stlawrencejuniorschoolkabowa.com/Fees.html'
    },
    {
        oldSrc: 'Contact-redesign.html',
        cleanName: 'Contact.html',
        rootAliases: ['Contact.html', 'contact.html', 'Contact-redesign.html'],
        canonical: 'https://stlawrencejuniorschoolkabowa.com/Contact.html'
    },
    {
        oldSrc: 'Teachers-redesign.html',
        cleanName: 'Teachers.html',
        rootAliases: ['Teachers.html', 'teachers.html', 'Teachers-redesign.html'],
        canonical: 'https://stlawrencejuniorschoolkabowa.com/Teachers.html'
    },
    {
        oldSrc: 'Gallery-redesign.html',
        cleanName: 'Gallery.html',
        rootAliases: ['Gallery.html', 'gallery.html', 'Gallery-redesign.html'],
        canonical: 'https://stlawrencejuniorschoolkabowa.com/Gallery.html'
    },
    {
        oldSrc: 'Library-redesign.html',
        cleanName: 'Library.html',
        rootAliases: ['Library.html', 'library.html', 'Library-redesign.html'],
        canonical: 'https://stlawrencejuniorschoolkabowa.com/Library.html'
    },
    {
        oldSrc: 'School-Anthem.html',
        cleanName: 'School-Anthem.html',
        rootAliases: ['School-Anthem.html', 'Anthem.html', 'anthem.html', 'school-anthem.html'],
        canonical: 'https://stlawrencejuniorschoolkabowa.com/School-Anthem.html'
    },
    {
        oldSrc: 'Teacher-Profile.html',
        cleanName: 'Teacher-Profile.html',
        rootAliases: ['Teacher-Profile.html', 'teacher-profile.html'],
        canonical: 'https://stlawrencejuniorschoolkabowa.com/Teacher-Profile.html'
    }
];

function replaceAllRedesignLinks(content) {
    let result = content;

    // Replace -redesign links with clean names
    result = result.replace(/About-redesign\.html/gi, 'About.html');
    result = result.replace(/Admission-redesign\.html/gi, 'Admission.html');
    result = result.replace(/Contact-redesign\.html/gi, 'Contact.html');
    result = result.replace(/Gallery-redesign\.html/gi, 'Gallery.html');
    result = result.replace(/Library-redesign\.html/gi, 'Library.html');
    result = result.replace(/Teachers-redesign\.html/gi, 'Teachers.html');
    result = result.replace(/index-redesign\.html/gi, 'index.html');

    return result;
}

function transformForRoot(content, page) {
    let result = replaceAllRedesignLinks(content);

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

    // Normalize home link
    result = result.replace(/href=["'](\.\.\/)*index\.html["']/g, 'href="/"');
    result = result.replace(/href=["']index\.html["']/g, 'href="/"');

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

console.log('--- Starting Migration to Normal Page Names ---');

// 1. Process each page
PAGES.forEach(page => {
    // Determine source: cleanName or oldSrc
    let srcPath = path.join(FRONTEND_DIR, page.cleanName);
    if (!fs.existsSync(srcPath)) {
        srcPath = path.join(FRONTEND_DIR, page.oldSrc);
    }

    if (!fs.existsSync(srcPath)) {
        console.error(`Cannot find source for ${page.cleanName}`);
        return;
    }

    let raw = fs.readFileSync(srcPath, 'utf8');

    // Update internal links in frontend content
    let frontendContent = replaceAllRedesignLinks(raw);

    // Save in frontend/ with clean name
    const frontendCleanPath = path.join(FRONTEND_DIR, page.cleanName);
    fs.writeFileSync(frontendCleanPath, frontendContent, 'utf8');
    console.log(`[Frontend] Saved clean page: frontend/${page.cleanName}`);

    // If cleanName != oldSrc, also create forwarder in frontend/oldSrc
    if (page.cleanName !== page.oldSrc) {
        const oldFrontendPath = path.join(FRONTEND_DIR, page.oldSrc);
        const forwardTarget = page.cleanName === 'index.html' ? '/' : `/${page.cleanName}`;
        fs.writeFileSync(oldFrontendPath, createRedirectHTML(forwardTarget), 'utf8');
        console.log(`[Frontend] Created legacy redirect: frontend/${page.oldSrc} -> ${forwardTarget}`);
    }

    // 2. Generate root pages
    const rootContent = transformForRoot(frontendContent, page);

    page.rootAliases.forEach(alias => {
        const rootPath = path.join(ROOT_DIR, alias);
        // If alias is the old redesign name, write a redirect to clean name instead of duplicating
        if (alias.toLowerCase().includes('-redesign')) {
            const cleanTarget = page.cleanName === 'index.html' ? '/' : `/${page.cleanName}`;
            fs.writeFileSync(rootPath, createRedirectHTML(cleanTarget), 'utf8');
            console.log(`[Root] Created legacy redirect: ${alias} -> ${cleanTarget}`);
        } else {
            fs.writeFileSync(rootPath, rootContent, 'utf8');
            console.log(`[Root] Generated page: ${alias}`);
        }
    });
});

// Update backend/admin/login.html if needed
const loginHtmlPath = path.join(ROOT_DIR, 'backend', 'admin', 'login.html');
if (fs.existsSync(loginHtmlPath)) {
    let loginContent = fs.readFileSync(loginHtmlPath, 'utf8');
    loginContent = loginContent.replace(/index-redesign\.html/g, 'index.html');
    fs.writeFileSync(loginHtmlPath, loginContent, 'utf8');
    console.log('[Backend] Updated backend/admin/login.html link to index.html');
}

// Update backend/api/helpers/AdmissionEmailService.php if needed
const emailServicePath = path.join(ROOT_DIR, 'backend', 'api', 'helpers', 'AdmissionEmailService.php');
if (fs.existsSync(emailServicePath)) {
    let emailContent = fs.readFileSync(emailServicePath, 'utf8');
    emailContent = emailContent.replace(/Admission-redesign\.html/g, 'Admission.html');
    fs.writeFileSync(emailServicePath, emailContent, 'utf8');
    console.log('[Backend] Updated AdmissionEmailService.php link to Admission.html');
}

console.log('--- Migration Completed Successfully ---');
