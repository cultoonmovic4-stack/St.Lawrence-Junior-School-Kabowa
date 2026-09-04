<?php
/**
 * Test script for knowledge base
 */

require_once 'knowledge_base.php';

$kb = new KnowledgeBase();

// Test questions
$testQuestions = [
    "What are your school fees?",
    "Tell me about uniforms",
    "What are your school hours?",
    "Where are you located?",
    "How many years of experience do you have?",
    "What is your contact information?",
    "Do you offer boarding?",
    "What are the uniform prices?",
    "Tell me about your teachers",
    "Who is the headteacher?",
    "What is your email address?",
    "How can I contact you?",
    "Where exactly is the school?",
    "Who can I talk to about admissions?"
];

echo "=== KNOWLEDGE BASE TEST ===\n\n";

foreach ($testQuestions as $question) {
    echo "Q: $question\n";
    $result = $kb->findAnswer($question);
    echo "Found: " . ($result['found'] ? 'YES' : 'NO') . "\n";
    echo "Category: " . $result['category'] . "\n";
    echo "Response: " . substr($result['response'], 0, 100) . "...\n";
    echo "\n---\n\n";
}
