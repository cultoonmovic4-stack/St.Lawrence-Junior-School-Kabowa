<?php
/**
 * submit_contact.php — Contact form submission endpoint
 * Anti-spam layers:
 *  1. Honeypot field check
 *  2. Time-gate (reject submissions faster than 4 seconds)
 *  3. Rate limiting per IP (max 3 per hour)
 *  4. Input validation & length limits
 *  5. Server-side spam keyword filter
 *  6. URL density check (too many links = spam)
 */

header('Content-Type: application/json');
// Tighten CORS — only allow your own site domain when deployed
// Change '*' below to your real domain e.g. 'https://www.stlawrencekabowa.ug'
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/Database.php';

// ─── Only accept POST ────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// ─── Read & decode JSON body ─────────────────────────────────────────────────
$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

// ─── Helper: sanitise a string ───────────────────────────────────────────────
function sanitise(string $str): string {
    return htmlspecialchars(strip_tags(trim($str)), ENT_QUOTES, 'UTF-8');
}

// ─── Helper: generic spam-reject response (don't reveal exact reason) ────────
function rejectSpam(string $reason = ''): void {
    // Log the real reason server-side for debugging, but don't expose it
    error_log('[Contact Spam Blocked] ' . $reason . ' | IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    http_response_code(400);
    echo json_encode([
        'success'  => false,
        'message'  => 'Your message could not be sent. Please try again or contact us directly by phone.'
    ]);
    exit;
}

// ════════════════════════════════════════════════════════════════════════════
//  LAYER 1 — Honeypot check
//  The hidden field "_hp" must be empty. Bots auto-fill it.
// ════════════════════════════════════════════════════════════════════════════
$honeypot = isset($data['_hp']) ? trim((string)$data['_hp']) : '';
if ($honeypot !== '') {
    rejectSpam("Honeypot triggered: '$honeypot'");
}

// ════════════════════════════════════════════════════════════════════════════
//  LAYER 2 — Time-gate
//  If submission arrives in less than 4 seconds from page load, it's a bot.
// ════════════════════════════════════════════════════════════════════════════
$loadedAt = isset($data['_ts']) ? (int)$data['_ts'] : 0;
$now      = time();
if ($loadedAt > 0 && ($now - $loadedAt) < 4) {
    rejectSpam("Time-gate: submitted in " . ($now - $loadedAt) . "s");
}
// Also reject if timestamp is in the future or more than 2 hours old
if ($loadedAt > 0 && ($loadedAt > $now + 60 || $loadedAt < $now - 7200)) {
    rejectSpam("Suspicious timestamp: $loadedAt");
}

// ════════════════════════════════════════════════════════════════════════════
//  LAYER 3 — Rate limiting per IP (max 3 submissions per hour)
// ════════════════════════════════════════════════════════════════════════════
try {
    $database = new Database();
    $db       = $database->getConnection();

    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    $rateStmt = $db->prepare("
        SELECT COUNT(*) AS cnt
        FROM contact_submissions
        WHERE ip_address = :ip
          AND submitted_date >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
    ");
    $rateStmt->bindParam(':ip', $ip);
    $rateStmt->execute();
    $rateRow = $rateStmt->fetch(PDO::FETCH_ASSOC);

    if ((int)($rateRow['cnt'] ?? 0) >= 3) {
        http_response_code(429);
        echo json_encode([
            'success' => false,
            'message' => 'Too many messages sent. Please wait a while before trying again.'
        ]);
        exit;
    }
} catch (Exception $e) {
    // If rate-limit check fails (e.g. column doesn't exist yet), continue gracefully
    error_log('[Contact Rate-Limit Error] ' . $e->getMessage());
    $db = $db ?? null;
}

// ════════════════════════════════════════════════════════════════════════════
//  LAYER 4 — Required field validation & length limits
// ════════════════════════════════════════════════════════════════════════════
$name    = sanitise($data['name']    ?? '');
$email   = trim($data['email']       ?? '');
$phone   = sanitise($data['phone']   ?? '');
$subject = sanitise($data['subject'] ?? '');
$message = sanitise($data['message'] ?? '');

if (empty($name) || empty($email) || empty($subject) || empty($message)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'All required fields must be filled in.']);
    exit;
}

// Enforce minimum lengths
if (mb_strlen($name) < 2)    { http_response_code(400); echo json_encode(['success'=>false,'message'=>'Name is too short.']);    exit; }
if (mb_strlen($subject) < 3) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'Subject is too short.']); exit; }
if (mb_strlen($message) < 20){ http_response_code(400); echo json_encode(['success'=>false,'message'=>'Please provide more detail in your message (at least 20 characters).']); exit; }

// Enforce maximum lengths
if (mb_strlen($name) > 100 || mb_strlen($email) > 150 || mb_strlen($subject) > 150 || mb_strlen($message) > 2000 || mb_strlen($phone) > 20) {
    rejectSpam("Field too long");
}

// ─── Email format ────────────────────────────────────────────────────────────
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit;
}

// ════════════════════════════════════════════════════════════════════════════
//  LAYER 5 — Spam keyword filter
// ════════════════════════════════════════════════════════════════════════════
$SPAM_KEYWORDS = [
    'make money fast', 'click here', 'free money', 'online casino',
    'lottery winner', 'you have been selected', 'earn from home',
    'work from home and earn', 'payday loan', 'cryptocurrency investment',
    'bitcoin profit', 'buy followers', 'seo service', 'cheap viagra',
    'adult content', 'hot singles', 'meet sexy', 'weight loss pill',
    'diet pill', 'act now', 'limited time offer', 'congratulations you won',
    'nigerian prince', 'wire transfer', 'money transfer urgent',
    'investment opportunity', 'double your money', 'risk free',
    'no credit check', 'debt relief', 'earn $', 'earn €',
    'guaranteed profit', 'mlm', 'multi-level marketing', 'pyramid scheme',
    'part time job offer', 'work online earn', 'passive income guaranteed',
];

$fullText = mb_strtolower($subject . ' ' . $message);
foreach ($SPAM_KEYWORDS as $keyword) {
    if (str_contains($fullText, $keyword)) {
        rejectSpam("Spam keyword: '$keyword'");
    }
}

// ════════════════════════════════════════════════════════════════════════════
//  LAYER 6 — URL density (more than 2 URLs = very likely spam)
// ════════════════════════════════════════════════════════════════════════════
$urlCount = preg_match_all('/https?:\/\/|www\.\S+/i', $message);
if ($urlCount > 2) {
    rejectSpam("Too many URLs in message: $urlCount");
}

// ════════════════════════════════════════════════════════════════════════════
//  LAYER 7 — Subject must have at least 2 words (not just "hi" or "test")
// ════════════════════════════════════════════════════════════════════════════
if (str_word_count($subject) < 2) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Please enter a more descriptive subject.']);
    exit;
}

// ════════════════════════════════════════════════════════════════════════════
//  All checks passed — Save to database
// ════════════════════════════════════════════════════════════════════════════
try {
    if (!isset($db) || $db === null) {
        $database = new Database();
        $db = $database->getConnection();
    }

    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    // NOTE: if your contact_submissions table doesn't have ip_address column yet,
    // run: ALTER TABLE contact_submissions ADD COLUMN ip_address VARCHAR(45) DEFAULT NULL;
    $stmt = $db->prepare("
        INSERT INTO contact_submissions
            (name, email, phone, subject, message, status, ip_address, submitted_date)
        VALUES
            (:name, :email, :phone, :subject, :message, 'new', :ip, NOW())
    ");

    $stmt->bindParam(':name',    $name);
    $stmt->bindParam(':email',   $email);
    $stmt->bindParam(':phone',   $phone);
    $stmt->bindParam(':subject', $subject);
    $stmt->bindParam(':message', $message);
    $stmt->bindParam(':ip',      $ip);

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Thank you for contacting us! We will get back to you soon.'
        ]);
    } else {
        throw new Exception('Failed to save message');
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred. Please try again or call us directly.'
    ]);
    error_log('[Contact Submit Error] ' . $e->getMessage());
}
