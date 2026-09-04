<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../config/Database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $debug = [];
    
    // Check admission_applications table
    try {
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM admission_applications");
        $stmt->execute();
        $debug['admissions_total'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Check table structure
        $stmt = $db->prepare("DESCRIBE admission_applications");
        $stmt->execute();
        $debug['admissions_columns'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Get sample admission records with all columns
        $stmt = $db->prepare("SELECT * FROM admission_applications LIMIT 3");
        $stmt->execute();
        $debug['admissions_sample'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Check this month's admissions - try different date column names
        $dateColumns = ['submitted_date', 'created_at', 'submission_date', 'date_created', 'application_date'];
        $admissionsThisMonth = 0;
        
        foreach ($dateColumns as $dateCol) {
            if (in_array($dateCol, $debug['admissions_columns'])) {
                try {
                    $stmt = $db->prepare("
                        SELECT COUNT(*) as this_month 
                        FROM admission_applications 
                        WHERE DATE_FORMAT($dateCol, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')
                    ");
                    $stmt->execute();
                    $admissionsThisMonth = $stmt->fetch(PDO::FETCH_ASSOC)['this_month'];
                    $debug['admissions_date_column_used'] = $dateCol;
                    break;
                } catch (Exception $e) {
                    continue;
                }
            }
        }
        
        $debug['admissions_this_month'] = $admissionsThisMonth;
        
    } catch (Exception $e) {
        $debug['admissions_error'] = $e->getMessage();
    }
    
    // Check contact_submissions table (messages from contact forms)
    try {
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM contact_submissions");
        $stmt->execute();
        $debug['messages_total'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Check table structure
        $stmt = $db->prepare("DESCRIBE contact_submissions");
        $stmt->execute();
        $debug['messages_columns'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Get sample contact submission records
        $stmt = $db->prepare("SELECT * FROM contact_submissions LIMIT 3");
        $stmt->execute();
        $debug['messages_sample'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Check unread messages based on status column
        if (in_array('status', $debug['messages_columns'])) {
            $stmt = $db->prepare("
                SELECT COUNT(*) as unread 
                FROM contact_submissions 
                WHERE status = 'new' OR status = 'unread'
            ");
            $stmt->execute();
            $debug['messages_unread'] = $stmt->fetch(PDO::FETCH_ASSOC)['unread'];
        }
        
    } catch (Exception $e) {
        $debug['messages_error'] = $e->getMessage();
    }
    
    // Check teachers table
    try {
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM teachers");
        $stmt->execute();
        $debug['teachers_total'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Check table structure
        $stmt = $db->prepare("DESCRIBE teachers");
        $stmt->execute();
        $debug['teachers_columns'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
    } catch (Exception $e) {
        $debug['teachers_error'] = $e->getMessage();
    }
    
    // Check events table
    try {
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM events");
        $stmt->execute();
        $debug['events_total'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Check table structure
        $stmt = $db->prepare("DESCRIBE events");
        $stmt->execute();
        $debug['events_columns'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
    } catch (Exception $e) {
        $debug['events_error'] = $e->getMessage();
    }
    
    echo json_encode([
        'success' => true,
        'debug' => $debug
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>