<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../config/Database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $fetchAll = isset($_GET['all']) || (isset($_GET['scope']) && $_GET['scope'] === 'calendar');
    $calWhere = $fetchAll ? '' : 'WHERE start_date >= CURDATE()';
    $eventsWhere = $fetchAll ? '' : "WHERE event_date >= CURDATE() AND status = 'upcoming'";
    $limitSql = $fetchAll ? '' : 'LIMIT 10';

    // Query both calendar_events (Important Days from Admin Dashboard)
    // and events (General Events from Admin Dashboard)
    $stmt = $db->prepare("
        SELECT 
            event_title,
            event_date,
            event_type,
            location,
            description,
            source
        FROM (
            SELECT 
                event_title,
                start_date AS event_date,
                event_type,
                '' AS location,
                COALESCE(event_description, '') AS description,
                'calendar_events' AS source,
                created_at
            FROM calendar_events
            $calWhere
            
            UNION ALL
            
            SELECT 
                title AS event_title,
                event_date,
                category AS event_type,
                COALESCE(location, '') AS location,
                COALESCE(description, '') AS description,
                'events' AS source,
                created_at
            FROM events
            $eventsWhere
        ) AS combined_events
        ORDER BY event_date ASC
        $limitSql
    ");
    $stmt->execute();
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fallback: If no future events exist, fetch the most recent entries
    if (empty($events)) {
        $stmtFallback = $db->prepare("
            SELECT 
                event_title,
                event_date,
                event_type,
                location,
                description,
                source
            FROM (
                SELECT 
                    event_title,
                    start_date AS event_date,
                    event_type,
                    '' AS location,
                    COALESCE(event_description, '') AS description,
                    'calendar_events' AS source,
                    created_at
                FROM calendar_events
                
                UNION ALL
                
                SELECT 
                    title AS event_title,
                    event_date,
                    category AS event_type,
                    COALESCE(location, '') AS location,
                    COALESCE(description, '') AS description,
                    'events' AS source,
                    created_at
                FROM events
            ) AS combined_all
            ORDER BY created_at DESC, event_date DESC
            LIMIT 5
        ");
        $stmtFallback->execute();
        $events = $stmtFallback->fetchAll(PDO::FETCH_ASSOC);
    }
    
    echo json_encode([
        'success' => true,
        'data' => $events
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
