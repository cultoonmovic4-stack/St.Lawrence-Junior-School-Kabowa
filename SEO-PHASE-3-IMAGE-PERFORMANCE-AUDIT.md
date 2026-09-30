# St. Lawrence Junior School — SEO Phase 3 Image & Performance Audit

**Target Institution:** St. Lawrence Junior School – Kabowa  
**Production URL:** `https://cultoonmovic4-stack.github.io/St.Lawrence-Junior-School-Kabowa/`  
**Audit Date:** September 30, 2026  
**Auditor:** Antigravity (Senior Technical SEO & Frontend Performance Engineer)  
**Status:** Pre-Implementation Baseline Audit  

---

## 1. Executive Summary

This document presents the pre-implementation audit for **Phase 3: Image SEO & Performance**. 

The website currently contains 374 image files in the repository. While Phase 1 established strong crawl hygiene and Phase 2 deployed complete metadata and structured data, visual performance currently suffers from several severe image-related bottlenecks:
1. **Extreme Oversized Assets:** Several pages, particularly `About-redesign.html`, load uncompressed camera-raw photographs (`new 7.JPG`, `new 6.JPG`, `new 2.JPG`, `new 3.JPG`) ranging from **8.2 MB to 13.8 MB each**, resulting in over **40.9 MB** of image data for a single page load.
2. **Heavy Shared Hero Photography:** The primary school photograph `img/hero-students-target.jpg` (1376x768px, 783.5 KB) serves as the primary visual component across the Homepage hero, About page, Fees hero, School Anthem hero, Gallery banner, and social cards. Optimizing this single asset will yield massive bandwidth savings across the entire site.
3. **Cumulative Layout Shift (CLS) Risk:** Out of 72 `<img>` tags in public HTML files, **68 tags lack explicit `width` and `height` attributes**, forcing browsers to reflow the layout as images load.
4. **Underutilized Modern Formats:** Nearly all photographic assets are stored exclusively as baseline `.jpg` or `.png`. Modern `.webp` delivery is almost entirely absent.
5. **Inconsistent Lazy Loading:** Only 19 of the 72 `<img>` elements currently specify `loading="lazy"`, meaning off-screen images compete for bandwidth during initial page load.

---

## 2. Global Image Inventory & Format Breakdown

A comprehensive scan of the repository identified **374 image files**:

| Format | File Count | Total Size on Disk | Primary Usage |
| :--- | :--- | :--- | :--- |
| **JPEG (`.jpg` / `.jpeg` / `.JPG`)** | 358 | ~215 MB | School photography, pupil activities, campus history, staff portraits |
| **PNG (`.png`)** | 14 | ~2.1 MB | Official school crests, transparent logos, admissions graphics |
| **WebP (`.webp`)** | 2 | ~357 KB | Limited campus gate images |
| **Total** | **374** | **~217.5 MB** | |

### Top 15 Largest Image Files on Disk
| Relative Path | Size | Native Dimensions | Usage in Codebase |
| :--- | :--- | :--- | :--- |
| `img/contemplative-student-idea-stationery-wood.jpg` | 15.9 MB | 5600 x 3959 | Unreferenced stock asset |
| `img/camera-binoculars-near-globe-clouds.jpg` | 15.1 MB | 7360 x 4912 | Unreferenced stock asset |
| `img/new 7.JPG` | **13.8 MB** | 6240 x 4160 | `About-redesign.html` (Milestone 6: Campus grounds) |
| `img/new 4.JPG` | 11.4 MB | 6240 x 4160 | Unreferenced gallery candidate |
| `img/new 5.JPG` | 11.2 MB | 6240 x 4160 | Unreferenced gallery candidate |
| `img/new 6.JPG` | **10.6 MB** | 6240 x 4160 | `About-redesign.html` (Campus virtual tour thumbnail) |
| `img/scientists-look-sky-blue-chemicals-glass-laboratory.jpg` | 10.0 MB | 6048 x 4024 | Unreferenced stock asset |
| `img/new 1.JPG` | 8.6 MB | 6240 x 4160 | Unreferenced gallery candidate |
| `img/new 2.JPG` | **8.4 MB** | 6240 x 4160 | `About-redesign.html` (Milestone 1: 2010 building) |
| `img/IMG_0228.JPG` | 8.3 MB | 6240 x 4160 | Unreferenced raw photo |
| `img/new 3.JPG` | **8.2 MB** | 6240 x 4160 | `About-redesign.html` (Milestone 2: 2013 school van) |
| `img/medium-shot-smiley-woman-teaching.jpg` | 6.7 MB | 5428 x 3619 | Unreferenced stock asset |
| `img/IMG_0226.JPG` | 6.4 MB | 6240 x 4160 | Unreferenced raw photo |
| `img/program-day-schooling.jpg` | **1.05 MB** | 1200 x 896 | `index-redesign.html` (Day Schooling card) |
| `img/hero-students-target.jpg` | **783.5 KB** | 1376 x 768 | Primary Hero / OG image across 8 pages |

---

## 3. Public Pages Image Usage Audit

### Audit Summary across Public Pages (72 `<img>` Tags):
* **Explicit Dimensions (`width`/`height`):** 4 present (5.5%), **68 missing (94.5%)**.
* **Alt Attributes:** 71 present with descriptive text (98.6%), 1 empty (`alt=""`), 0 missing `alt` attribute.
* **Lazy Loading (`loading="lazy"`):** 19 present (26.4%), **53 missing (73.6%)**.

### Page-by-Page Audit Matrix

| Page | `<img>` Count | Key Images Used | Missing Dimensions | Missing Lazy Loading | Alt Quality |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **`index-redesign.html`** | 16 | Logo (`5.jpg`, `5-transparent.png`), Desk (`about-student-desk.jpg`), Pair (`about-students-pair.jpg`), Programs (`program-nursery.jpg`, `program-primary.jpg`, `program-day-schooling.jpg` [1.05MB!], `program-boarding.jpg`), Writing (`why-student-writing.jpg`), Director (`director-kimera.jpg`), Testimonial | 16 | 6 (hero & header correctly eager; testimonial missing lazy) | High |
| **`About-redesign.html`** | 18 | Crest, Logo, `hero-students-target.jpg` (783KB), `mission-student-hand.jpg`, `new 2.JPG` (8.4MB!), `new 3.JPG` (8.2MB!), `52.jpg` (684KB), `58.jpg` (737KB), `124.jpg` (293KB), `new 7.JPG` (13.8MB!), `new 6.JPG` (10.6MB!), Entrance (`about-cta-entrance.jpg`) | 18 | 7 (above-fold eager; 11 below-fold lack dimensions) | Excellent descriptions |
| **`Admission-redesign.html`** | 5 | Crest, Logo, Hero Students (`admissions-hero-students.png`, 667KB, 594x546) | 3 | 0 (hero eager) | High |
| **`Fees.html`** | 4 | Crest, Logo, Hero Students (`hero-students-target.jpg`, 783.5KB, 1376x768) | 2 | 0 (hero eager) | High |
| **`Teachers-redesign.html`** | 4 static + JS | Crest, Logo, Watermarks, Dynamic staff cards via API | 4 | Handled in JS | Good |
| **`Teacher-Profile.html`** | 4 static + JS | Crest, Logo, Watermarks, Dynamic profile avatar | 4 | Handled in JS | Good |
| **`Gallery-redesign.html`** | 7 static + JS | Crest, Logo, Hero Banner (`hero-students-target.jpg`), Dynamic grid | 7 | Grid cards use `loading="lazy"` | Good |
| **`Library-redesign.html`** | 3 | Crest, Logo | 3 | 0 (header/footer logos) | Good |
| **`School-Anthem.html`** | 5 | Crest, Logo, Hero (`hero-students-target.jpg`), Badge (`badge ps.jpg`, 541KB, 2480x3508) | 3 | 1 (`badge ps.jpg` lacks lazy & dimensions) | Good |
| **`Contact-redesign.html`** | 5 | Crest, Logo, Hero Students (`admissions-hero-students.png`, 667KB) | 3 | 0 | Good |
| **`index.html` (Root)** | 1 | Redirect fallback logo (`img/5.jpg`, 29.6KB, 180x180) | 1 | 0 | Good |

---

## 4. Identification of the True LCP Image

### Homepage (`frontend/index-redesign.html`):
* **Visual Structure:** The homepage viewport features an editorial hero (`.hero-target`) displaying:
  - Header with Logo badge (40x40px).
  - Prominent headline: `Nurturing Minds. Building Futures.`
  - Background photograph: `url('../img/hero-students-target.jpg')` declared in `css/navbar-hero-target.css`.
* **LCP Finding:** The largest visible element in the initial viewport across desktop and tablet is the background image of `.hero-target` (`img/hero-students-target.jpg`).
* **Performance Defect:** The file is 783.5 KB (JPEG). Because it is referenced in CSS rather than an HTML `<img>` tag, the browser preload scanner discovers it after parsing the stylesheet.
* **Optimization Requirement:**
  1. Optimize `hero-students-target.jpg` to a modern WebP derivative (`hero-students-target.webp`, ~136 KB, **82.6% reduction**).
  2. Deliver modern WebP in CSS with standard fallback.
  3. Ensure it is NEVER lazy-loaded.
  4. Consider preloading the LCP WebP candidate in `<head>` of `index-redesign.html`.

### Other Key Landing Pages LCP Candidates:
* **`About-redesign.html`**: Above-the-fold photo card uses `<img src="../img/hero-students-target.jpg">` (783.5 KB). Prioritize with `fetchpriority="high"` and WebP source.
* **`Admission-redesign.html`**: Hero features `admissions-hero-students.png` (667 KB, 594x546). Convert to WebP (~65 KB, **90% reduction**).
* **`Fees.html`**: Hero features `hero-students-target.jpg` (783.5 KB). Convert to WebP.
* **`School-Anthem.html`**: Hero features `hero-students-target.jpg` (783.5 KB). Convert to WebP.

---

## 5. Critical Performance Deficiencies & Opportunities

### 5.1 The 40.9 MB History Section on `About-redesign.html`
In `About-redesign.html`, lines 550–755 render a historical milestone timeline and a campus tour preview using four unresized camera originals:
* `img/new 2.JPG`: **8,386.9 KB** (6240 x 4160 px) rendered in a 580px column.
* `img/new 3.JPG`: **8,190.2 KB** (6240 x 4160 px) rendered in a 580px column.
* `img/new 7.JPG`: **13,775.0 KB** (6240 x 4160 px) rendered in a 580px column.
* `img/new 6.JPG`: **10,582.4 KB** (6240 x 4160 px) rendered as a 960px video thumbnail.

**Optimization Action:** Generate web-optimized 1200px wide WebP derivatives (`img/new-2.webp`, `img/new-3.webp`, `img/new-7.webp`, `img/new-6.webp`) compressed at high quality. This will drop total image payload from **40.9 MB down to ~600 KB (a 98.5% payload reduction)**.

### 5.2 The 1.05 MB Card on `index-redesign.html`
* `img/program-day-schooling.jpg` is **1,055 KB** (1200 x 896 px) rendered in a 360px card.
* **Optimization Action:** Generate `img/program-day-schooling.webp` (~50 KB, **95% reduction**).

### 5.3 The 541 KB Embroidered Badge on `School-Anthem.html`
* `img/badge ps.jpg` is **541 KB** (2480 x 3508 px) displayed as a small decorative emblem.
* **Optimization Action:** Generate `img/badge-ps.webp` (~45 KB, **91% reduction**) and provide explicit dimensions.

### 5.4 Layout Shift (CLS) from Missing Dimensions
68 `<img>` tags lack explicit `width` and `height` attributes. Supplying native or aspect-ratio-accurate dimensions will enable browsers to allocate box space prior to download, mitigating layout shift.

---

## 6. Action Plan for Phase 3 Implementation

1. **Asset Derivatives Generation:**
   - Create high-quality WebP derivatives of key referenced photography (`hero-students-target.webp`, `admissions-hero-students.webp`, `new-2.webp`, `new-3.webp`, `new-6.webp`, `new-7.webp`, `program-day-schooling.webp`, `badge-ps.webp`, `why-student-writing.webp`, `director-kimera.webp`, `about-student-desk.webp`, `about-students-pair.webp`, etc.).
   - Preserve all original JPEG/PNG files on disk for fallback compatibility and Open Graph social scrapers.
2. **Markup Updates:**
   - Supply explicit `width` and `height` attributes to all static `<img>` tags across all 10 public pages and root `index.html`.
   - Implement modern `<picture>` elements for major responsive heroes and timeline photos with `<source type="image/webp">` and fallback `<img>`.
   - Add `loading="lazy"` and `decoding="async"` to all below-the-fold images.
   - Add `fetchpriority="high"` strictly to the primary LCP candidate on each landing page.
3. **CSS Background Modernization:**
   - Update `css/navbar-hero-target.css` to prefer `hero-students-target.webp` with fallback.
4. **Dynamic Image Handling:**
   - Update dynamic card generation scripts in `Gallery-redesign.html`, `Teachers-redesign.html`, and `Teacher-Profile.html` to supply explicit dimensions and `decoding="async"`.
5. **Testing & Validation:**
   - Validate image references, HTTP paths, GitHub Pages relative URL correctness, and CLS attributes.
   - Run Phase 1 (85/85) and Phase 2 (299/299) regression suites to ensure zero SEO regressions.
