<?php
/**
 * Test Script for Expanded Knowledge Base
 * Tests all new categories: location, contacts, teachers, staff
 */

require_once 'knowledge_base.php';

$kb = new KnowledgeBase();

echo "=== TESTING EXPANDED KNOWLEDGE BASE ===\n\n";

// Test questions for new categories
$testQuestions = [
    // Location questions
    "Where exactly is the school located?",
    "How do I get to your school?",
    "What are the nearby landmarks?",
    "Can you give me directions?",
    
    // Contact person questions
    "Who can I talk to about admissions?",
    "Who handles school fees?",
    "Who is the bursar?",
    "Who should I contact for boarding?",
    
    // Email questions
    "What is your email address?",
    "How can I email you?",
    "What is your official email?",
    
    // Phone questions
    "What are your phone numbers?",
    "Can I call you?",
    "What is your contact number?",
    
    // Teacher questions
    "Tell me about your teachers",
    "How many teachers do you have?",
    "What are teacher qualifications?",
    "What is the teacher-student ratio?",
    
    // Headteacher questions
    "Who is the headteacher?",
    "Who is the principal?",
    "Who is in charge of the school?",
    
    // Office questions
    "Where is your office?",
    "What are office hours?",
    "Can I visit your office?",
    
    // Social media questions
    "Do you have a website?",
    "Are you on Facebook?",
    "Can I WhatsApp you?",
    
    // Staff structure questions
    "What departments do you have?",
    "Who handles what?",
    "Tell me about your staff structure"
];

$passed = 0;
$failed = 0;

foreach ($testQuestions as $index => $question) {
    echo "Test " . ($index + 1) . ": \"$question\"\n";
    
    $result = $kb->findAnswer($question);
    
    if ($result['found']) {
        echo "✅ PASSED - Category: {$result['category']}\n";
        echo "Response preview: " . substr($result['response'], 0, 100) . "...\n";
        $passed++;
    } else {
        echo "❌ FAILED - No match found\n";
        $failed++;
    }
    
    echo "\n" . str_repeat("-", 80) . "\n\n";
}

echo "\n=== TEST SUMMARY ===\n";
echo "Total Tests: " . count($testQuestions) . "\n";
echo "Passed: $passed ✅\n";
echo "Failed: $failed ❌\n";
echo "Success Rate: " . round(($passed / count($testQuestions)) * 100, 2) . "%\n";

if ($failed === 0) {
    echo "\n🎉 ALL TESTS PASSED! Knowledge base is fully comprehensive!\n";
} else {
    echo "\n⚠️ Some tests failed. Review the knowledge base.\n";
}
