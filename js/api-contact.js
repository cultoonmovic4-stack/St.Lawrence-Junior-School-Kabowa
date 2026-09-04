// Contact Form API Integration — with Anti-Spam protection
// Layers: honeypot | time-gate | content quality | server-side rate limit + keyword filter

// ─── Record the exact moment the page loaded so the server can check speed ───
(function () {
    const ts = document.getElementById('form_loaded_at');
    if (ts) ts.value = Math.floor(Date.now() / 1000); // Unix timestamp (seconds)
})();

// ─── Client-side content quality helpers ──────────────────────────────────────

/**
 * Count how many URLs/links appear in a string.
 * Spammers love pasting lots of http/www links.
 */
function countUrls(text) {
    const urlPattern = /https?:\/\/|www\.\S+/gi;
    const matches = text.match(urlPattern);
    return matches ? matches.length : 0;
}

/**
 * Rough "word variety" check — spam messages often repeat the same word
 * or are completely random characters with no real words.
 */
function looksLikeGibberish(text) {
    const words = text.trim().split(/\s+/);
    if (words.length < 3) return false; // too short to judge
    const unique = new Set(words.map(w => w.toLowerCase()));
    // If less than 30% of words are unique it's likely copy-pasted repetition
    return unique.size / words.length < 0.3;
}

/**
 * Detect obvious spam phrases (client-side early rejection).
 */
const SPAM_PHRASES = [
    'make money fast', 'click here', 'free money', 'casino', 'lottery',
    'you have been selected', 'earn from home', 'work from home and earn',
    'payday loan', 'cryptocurrency investment', 'bitcoin profit',
    'buy followers', 'seo service', 'cheap viagra', 'adult content',
    'hot singles', 'meet sexy', 'weight loss pill', 'diet pill',
    'act now', 'limited time offer', 'congratulations you won',
    'nigerian prince', 'wire transfer', 'money transfer urgent'
];

function containsSpamPhrases(text) {
    const lower = text.toLowerCase();
    return SPAM_PHRASES.some(phrase => lower.includes(phrase));
}

// ─── Main form submit handler ──────────────────────────────────────────────────
if (document.getElementById('contactForm')) {
    document.getElementById('contactForm').addEventListener('submit', async function (e) {
        e.preventDefault();

        const submitBtn = this.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn.innerHTML;

        // ── 1. Honeypot check (bots fill the hidden field, humans never do) ──
        const honeypot = document.getElementById('website_url');
        if (honeypot && honeypot.value.trim() !== '') {
            // Silently pretend success — don't tell bots they were caught
            showFormSuccess('Thank you! Your message has been received.');
            this.reset();
            return;
        }

        // ── 2. Time-gate: reject submissions faster than 5 seconds ──────────
        const loadedAt = parseInt(document.getElementById('form_loaded_at')?.value || '0', 10);
        const now = Math.floor(Date.now() / 1000);
        if (loadedAt > 0 && (now - loadedAt) < 5) {
            showFormError('Please take a moment to fill in the form before submitting.');
            return;
        }

        // ── 3. Collect form data ─────────────────────────────────────────────
        const name    = document.getElementById('name').value.trim();
        const email   = document.getElementById('email').value.trim();
        const phone   = document.getElementById('phone')?.value.trim() || '';
        const subject = document.getElementById('subject').value.trim();
        const message = document.getElementById('message').value.trim();

        // ── 4. Basic required field check ────────────────────────────────────
        if (!name || !email || !subject || !message) {
            showFormError('Please fill in all required fields.');
            return;
        }

        // ── 5. Minimum message length (at least 20 chars) ───────────────────
        if (message.length < 20) {
            showFormError('Your message is too short. Please provide more detail so we can help you properly.');
            return;
        }

        // ── 6. Too many URLs in the message ─────────────────────────────────
        if (countUrls(message) > 2) {
            showFormError('Your message contains too many links. Please describe your inquiry in your own words.');
            return;
        }

        // ── 7. Spam phrase detection ─────────────────────────────────────────
        if (containsSpamPhrases(subject) || containsSpamPhrases(message)) {
            showFormError('Your message was flagged as spam. Please write a genuine inquiry.');
            return;
        }

        // ── 8. Gibberish / repetition detection ──────────────────────────────
        if (looksLikeGibberish(message)) {
            showFormError('Your message does not appear to be a genuine inquiry. Please write clearly what you need help with.');
            return;
        }

        // ── 9. Subject must be meaningful (not just spaces / single word) ────
        if (subject.split(/\s+/).length < 2) {
            showFormError('Please enter a more descriptive subject so we know what your message is about.');
            return;
        }

        // ── 10. Send to server ───────────────────────────────────────────────
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';

        const formData = {
            name,
            email,
            phone,
            subject,
            message,
            form_type: 'Contact',
            // Pass these to server for server-side validation as well
            _hp: honeypot ? honeypot.value : '',
            _ts: loadedAt
        };

        try {
            const response = await API.post('/public/submit_contact.php', formData);

            if (response.success) {
                showFormSuccess('Thank you! Your message has been sent successfully. We will get back to you soon.');
                this.reset();
                // Reset the timestamp after successful submit
                const ts = document.getElementById('form_loaded_at');
                if (ts) ts.value = Math.floor(Date.now() / 1000);
            } else {
                showFormError(response.message || 'Failed to send message. Please try again.');
            }
        } catch (error) {
            showFormError('Connection error. Please check your internet and try again.');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        }
    });
}

// ─── Alert helpers ────────────────────────────────────────────────────────────
function showFormSuccess(message) {
    showSuccessAlert('Message Sent!', message);
}

function showFormError(message) {
    showErrorAlert('Oops!', message);
}
