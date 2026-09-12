<?php
/**
 * St. Lawrence Junior School Kabowa
 * Authoritative Admission Application PDF Generator
 * 
 * Generates:
 * 1. Submitted Application Dossier (populated with applicant data & digital signature)
 * 2. Printable Blank Application Form (matching the exact online field structure)
 */

require_once __DIR__ . '/fpdf/fpdf.php';

class AdmissionPDFGenerator extends FPDF {
    private $appData;
    private $schoolLogo;
    private $isBlank = false;

    public function __construct($applicationData = null, $isBlank = false) {
        parent::__construct('P', 'mm', 'A4');
        $this->appData = $applicationData ?? [];
        $this->isBlank = $isBlank;
        
        // Resolve official school crest
        $logoFile = realpath(__DIR__ . '/../../../img/5-transparent.png') 
                 ?: realpath(__DIR__ . '/../../../img/5.jpg');
        $this->schoolLogo = $logoFile ?: '';
        
        $this->SetAutoPageBreak(true, 15);
        $this->SetMargins(15, 14, 15);
    }

    public function Header() {
        if ($this->PageNo() == 1) {
            // Institutional Header on First Page
            if ($this->schoolLogo && file_exists($this->schoolLogo)) {
                $this->Image($this->schoolLogo, 15, 11, 22);
            }
            $this->SetXY(40, 11);
            $this->SetFont('Helvetica', 'B', 15);
            $this->SetTextColor(11, 37, 69); // School Navy Blue
            $this->Cell(0, 6.5, 'ST. LAWRENCE JUNIOR SCHOOL KABOWA', 0, 1, 'L');
            
            $this->SetX(40);
            $this->SetFont('Helvetica', '', 8.5);
            $this->SetTextColor(80, 80, 80);
            $this->Cell(0, 4.5, 'P. O. Box 36198, Kampala | Tel: 0772-420506 / 0701-420506 | Email: stlawrencejuniorschool@yahoo.com', 0, 1, 'L');
            
            $this->SetX(40);
            $this->SetFont('Helvetica', 'B', 8.5);
            $this->SetTextColor(201, 24, 43); // School Crimson Red
            $this->Cell(0, 4.5, 'MOTTO: "WE STRIVE TO EXCEL"', 0, 1, 'L');
            
            $this->Ln(3);
            $this->SetDrawColor(11, 37, 69);
            $this->SetLineWidth(0.6);
            $this->Line(15, $this->GetY(), 195, $this->GetY());
            $this->Ln(2.5);

            // Document Title Banner
            $this->SetFillColor(245, 247, 250);
            $this->SetDrawColor(218, 225, 233);
            $this->SetLineWidth(0.2);
            $this->Rect(15, $this->GetY(), 180, 10.5, 'DF');
            
            $this->SetFont('Helvetica', 'B', 10);
            $this->SetTextColor(11, 37, 69);
            $this->SetY($this->GetY() + 1);
            
            $titleText = $this->isBlank 
                ? 'OFFICIAL APPLICATION FOR ADMISSION - BLANK PRINTABLE FORM' 
                : 'OFFICIAL APPLICATION FOR ADMISSION - SUBMITTED RECORD';
            $this->Cell(120, 8.5, '  ' . $titleText, 0, 0, 'L');
            
            $this->SetFont('Helvetica', 'B', 9);
            $this->SetTextColor(80, 80, 80);
            $refText = $this->isBlank 
                ? 'OFFICIAL REGISTRY COPY' 
                : 'REF: ' . ($this->appData['application_reference'] ?? 'PENDING');
            $this->Cell(60, 8.5, $refText . '  ', 0, 1, 'R');
            $this->Ln(2);
        } else {
            // Running top header for subsequent pages
            $this->SetFont('Helvetica', 'I', 8);
            $this->SetTextColor(120, 120, 120);
            $docType = $this->isBlank ? 'Blank Application Form' : 'Submitted Application Record';
            $ref = $this->isBlank ? 'St. Lawrence Junior School Kabowa' : ('Ref: ' . ($this->appData['application_reference'] ?? ''));
            $this->Cell(110, 5, "St. Lawrence Junior School Kabowa - {$docType}", 0, 0, 'L');
            $this->Cell(70, 5, "{$ref} | Page " . $this->PageNo(), 0, 1, 'R');
            $this->SetDrawColor(220, 220, 220);
            $this->SetLineWidth(0.2);
            $this->Line(15, $this->GetY(), 195, $this->GetY());
            $this->Ln(3);
        }
    }

    public function Footer() {
        $this->SetY(-13);
        $this->SetFont('Helvetica', '', 8);
        $this->SetTextColor(130, 130, 130);
        $this->SetDrawColor(220, 220, 220);
        $this->Line(15, $this->GetY(), 195, $this->GetY());
        $this->Ln(1.5);
        $dateText = $this->isBlank 
            ? 'St. Lawrence Junior School Kabowa • Official Physical Application Form'
            : 'Submitted: ' . ($this->appData['submitted_date'] ?? date('Y-m-d H:i:s')) . ' • Verified Registry Record';
        $this->Cell(120, 5, $dateText, 0, 0, 'L');
        $this->Cell(60, 5, 'Page ' . $this->PageNo(), 0, 0, 'R');
    }

    private function renderSectionHeader($title) {
        $this->Ln(1.5);
        $this->SetFillColor(11, 37, 69);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Helvetica', 'B', 8.5);
        $this->Cell(180, 5.5, '   ' . strtoupper($title), 0, 1, 'L', true);
        $this->SetTextColor(20, 20, 20); // Explicitly restore visible dark text
        $this->Ln(1.5);
    }

    private function renderRow($label1, $val1, $label2 = null, $val2 = null) {
        $this->SetFont('Helvetica', 'B', 8.5);
        $this->SetTextColor(70, 70, 70);
        
        if ($label2 !== null) {
            $this->Cell(45, 5.5, $label1 . ':', 0, 0, 'L');
            $this->SetFont('Helvetica', '', 9);
            $this->SetTextColor(20, 20, 20);
            $this->Cell(45, 5.5, ($val1 !== null && $val1 !== '') ? $val1 : '-', 0, 0, 'L');
            
            $this->SetFont('Helvetica', 'B', 8.5);
            $this->SetTextColor(70, 70, 70);
            $this->Cell(45, 5.5, $label2 . ':', 0, 0, 'L');
            $this->SetFont('Helvetica', '', 9);
            $this->SetTextColor(20, 20, 20);
            $this->Cell(45, 5.5, ($val2 !== null && $val2 !== '') ? $val2 : '-', 0, 1, 'L');
        } else {
            $this->Cell(45, 5.5, $label1 . ':', 0, 0, 'L');
            $this->SetFont('Helvetica', '', 9);
            $this->SetTextColor(20, 20, 20);
            $this->MultiCell(135, 5.5, ($val1 !== null && $val1 !== '') ? $val1 : '-', 0, 'L');
        }
    }

    private function renderBlankRow($label1, $label2 = null) {
        $this->SetFont('Helvetica', 'B', 8.5);
        $this->SetTextColor(70, 70, 70);

        if ($label2 !== null) {
            $this->Cell(45, 5.8, $label1 . ':', 0, 0, 'L');
            $this->SetFont('Helvetica', '', 8.5);
            $this->SetTextColor(150, 150, 150);
            $this->Cell(45, 5.8, '................................................', 0, 0, 'L');

            $this->SetFont('Helvetica', 'B', 8.5);
            $this->SetTextColor(70, 70, 70);
            $this->Cell(45, 5.8, $label2 . ':', 0, 0, 'L');
            $this->SetFont('Helvetica', '', 8.5);
            $this->SetTextColor(150, 150, 150);
            $this->Cell(45, 5.8, '................................................', 0, 1, 'L');
        } else {
            $this->Cell(45, 5.8, $label1 . ':', 0, 0, 'L');
            $this->SetFont('Helvetica', '', 8.5);
            $this->SetTextColor(150, 150, 150);
            $this->Cell(135, 5.8, '........................................................................................................................', 0, 1, 'L');
        }
    }

    private function renderRulesList() {
        $this->SetFont('Helvetica', 'B', 8.5);
        $this->SetTextColor(11, 37, 69);
        $this->Cell(180, 5, 'RULES AND REGULATIONS', 0, 1, 'L');
        $this->Ln(1);

        $rules = [
            1  => "All school fees must be paid in the bank.",
            2  => "No cash is allowed to be kept by the child.",
            3  => "All medication/drugs must be kept in the sickbay.",
            4  => "No alcohol should be brought to the school at any one time.",
            5  => "Reporting to school after holidays and visitation days must be between 9:00am and 5:00p.m.",
            6  => "Children who stay away for two weeks without any communication to the office / Headteacher from the day a term begins will lose their places.",
            7  => "Every parent/guardian must present the visitor's card for the child before being given chance to talk to him/her or collect him/her from school.",
            8  => "Parents are not allowed in the dormitories on any other day part from the beginning and end of term.",
            9  => "All children must have a medical checkup every beginning of them before returning to school.",
            10 => "Parents/pupils should always give due respect to each other and all members of staff-teaching and non-teaching.",
            11 => "If a child has been staying with any one suffering from:\n     - Measles\n     - Mumps or any other epidemic, he/she should be left at home for at least a week before bringing him/her back to school.",
            12 => "It is not accepted to make your child's uniform outside the school."
        ];

        $this->SetFont('Helvetica', '', 8.2);
        $this->SetTextColor(40, 40, 40);

        foreach ($rules as $num => $ruleText) {
            $this->SetFont('Helvetica', 'B', 8.2);
            $this->Cell(6, 4.3, $num . '.', 0, 0, 'L');
            $this->SetFont('Helvetica', '', 8.2);
            $this->MultiCell(174, 4.3, $ruleText, 0, 'L');
            $this->Ln(1);
        }
    }

    /**
     * Primary entry point for submitted applications
     */
    public function generate($data = null) {
        return $this->generateSubmitted($data);
    }

    /**
     * Generate the complete Submitted Application Dossier
     */
    public function generateSubmitted($data = null) {
        if ($data) $this->appData = $data;
        $this->isBlank = false;
        $this->AddPage();

        $d = $this->appData;

        // PAGE 1
        // SECTION 1: APPLICATION DETAILS
        $this->renderSectionHeader('1. Application Information');
        $this->renderRow('Academic Year', $d['academic_year'] ?? '', 'Academic Term', $d['term'] ?? '');
        $this->renderRow('Class Applying For', $d['class_to_join'] ?? '', 'School Type', strtoupper($d['admission_type'] ?? 'Day School'));

        // SECTION 2: CHILD INFORMATION
        $this->renderSectionHeader('2. Child Information');
        $this->renderRow("Child's Surname", $d['student_surname'] ?? '', 'Other Names', $d['student_other_names'] ?? '');
        $this->renderRow('Date of Birth', $d['date_of_birth'] ?? '', 'Sex', strtoupper($d['gender'] ?? ''));
        $this->renderRow('Religion', $d['religion'] ?? '', 'Tribe', $d['tribe'] ?? '');
        $this->renderRow('Position in Family', $d['position_in_family'] ?? '', 'Attended School Before', (!empty($d['has_attended_school']) ? 'YES' : 'NO'));
        
        if (!empty($d['has_attended_school'])) {
            $this->renderRow('Previous School Name', $d['previous_school_name'] ?? '', 'Previous Location', $d['previous_school_location'] ?? '');
        }
        $this->renderRow('1st Language Spoken', $d['language_1'] ?? '', '2nd Language Spoken', $d['language_2'] ?? 'None');

        // SECTION 3: PERSON RESPONSIBLE & PARENTS
        $this->renderSectionHeader('3. Person Responsible & Parents Particulars');
        $this->renderRow('Person Responsible', $d['responsible_person_name'] ?? '', 'Telephone Number', $d['responsible_person_phone'] ?? '');
        $this->renderRow('Email Address', $d['responsible_person_email'] ?? 'None', 'Postal Address', $d['responsible_person_postal'] ?? 'None');
        $this->renderRow('Home / Contact Address', $d['responsible_person_address'] ?? '', 'Parents Live Together', strtoupper($d['parents_live_together'] ?? 'YES'));
        
        // Father
        $this->renderRow("Father's Name", $d['father_name'] ?? '', "Working As", $d['father_occupation'] ?? '');
        $this->renderRow("Father's Workplace", $d['father_workplace'] ?? '', "Father's Address", $d['father_address'] ?? '');

        // Mother
        $this->renderRow("Mother's Name", $d['mother_name'] ?? '', "Working As", $d['mother_occupation'] ?? '');
        $this->renderRow("Mother's Workplace", $d['mother_workplace'] ?? '', "Mother's Address", $d['mother_address'] ?? '');

        // PAGE 2
        $this->AddPage();

        // SECTION 4: EMERGENCY & HEALTH
        $this->renderSectionHeader('4. Emergency & Health Information');
        $this->renderRow('Next of Kin in Emergency', $d['emergency_next_of_kin'] ?? '', 'Day School / Boarder', strtoupper($d['admission_type'] ?? 'Day School'));
        
        if (($d['admission_type'] ?? '') === 'boarding') {
            $this->renderRow('Wets Bed (Boarder)', strtoupper($d['bed_wetting'] ?? 'NO'));
        }
        
        $this->renderRow('Had Health Handicap?', strtoupper($d['has_health_handicap'] ?? 'NO'), 'Immunized (Measles)', strtoupper($d['is_immunized'] ?? 'YES'));
        
        if (($d['has_health_handicap'] ?? '') === 'yes' || !empty($d['health_handicap_details'])) {
            $this->renderRow('Handicap Details', $d['health_handicap_details']);
        }
        
        $this->renderRow("Child's Doctor", $d['doctor_name'] ?? 'None', "Doctor's Location", $d['doctor_location'] ?? 'None');

        // SECTION 5: OTHER INFORMATION
        $this->renderSectionHeader('5. Other Information');
        $this->renderRow('Other Information of Use', $d['other_information'] ?? 'None provided');

        // SECTION 6: RULES & REGULATIONS
        $this->renderSectionHeader('6. School Rules & Regulations');
        $this->renderRulesList();

        // PAGE 3
        $this->AddPage();

        // SECTION 7: DECLARATION & SIGNATURE
        $this->renderSectionHeader('7. Parent / Guardian Declaration & Signature');
        
        $parentName = $d['declaration_name'] ?? ($d['responsible_person_name'] ?? '');
        $studentName = trim(($d['student_surname'] ?? '') . ' ' . ($d['student_other_names'] ?? ''));
        $classToJoin = $d['class_to_join'] ?? '';

        $this->SetDrawColor(218, 225, 233);
        $this->SetFillColor(250, 252, 255);
        $ackBoxY = $this->GetY();
        $this->Rect(15, $ackBoxY, 180, 24, 'DF');

        $this->SetXY(18, $ackBoxY + 3);
        $this->SetFont('Helvetica', 'B', 8.5);
        $this->SetTextColor(11, 37, 69);
        $this->Cell(174, 5, 'OFFICIAL RULES & REGULATIONS ACKNOWLEDGEMENT:', 0, 1, 'L');
        
        $this->SetX(18);
        $this->SetFont('Helvetica', 'I', 8.5);
        $this->SetTextColor(40, 40, 40);
        $ackText = "I {$parentName} Parent/Guardian of {$studentName} Class {$classToJoin}, I have read the rules and regulations, and I am ready to abide.";
        $this->MultiCell(174, 4.5, $ackText, 0, 'L');

        $this->SetY($ackBoxY + 27);

        // Digital Signature Box
        $sigBoxY = $this->GetY();
        $this->SetDrawColor(218, 225, 233);
        $this->SetFillColor(255, 255, 255);
        $this->Rect(15, $sigBoxY, 180, 36, 'DF');

        $this->SetXY(18, $sigBoxY + 3);
        $this->SetFont('Helvetica', 'B', 8.5);
        $this->SetTextColor(70, 70, 70);
        $this->Cell(50, 5, 'Parent/Guardian Full Name:', 0, 0, 'L');
        $this->SetFont('Helvetica', 'B', 9.5);
        $this->SetTextColor(11, 37, 69);
        $this->Cell(70, 5, $parentName, 0, 1, 'L');

        $this->SetXY(18, $sigBoxY + 10);
        $this->SetFont('Helvetica', 'B', 8.5);
        $this->SetTextColor(70, 70, 70);
        $this->Cell(50, 5, 'Date of Submission:', 0, 0, 'L');
        $this->SetFont('Helvetica', '', 9);
        $this->SetTextColor(20, 20, 20);
        $this->Cell(70, 5, $d['submitted_date'] ?? date('Y-m-d H:i:s'), 0, 1, 'L');

        $this->SetXY(18, $sigBoxY + 17);
        $this->SetFont('Helvetica', 'B', 8.5);
        $this->SetTextColor(70, 70, 70);
        $this->Cell(50, 5, 'Application Reference:', 0, 0, 'L');
        $this->SetFont('Helvetica', 'B', 9);
        $this->SetTextColor(11, 37, 69);
        $this->Cell(70, 5, $d['application_reference'] ?? 'PENDING', 0, 1, 'L');

        $this->SetXY(18, $sigBoxY + 24);
        $this->SetFont('Helvetica', 'B', 8.5);
        $this->SetTextColor(70, 70, 70);
        $this->Cell(50, 5, 'Official Declaration:', 0, 0, 'L');
        $this->SetFont('Helvetica', '', 8.5);
        $this->SetTextColor(22, 101, 52); // Green
        $this->Cell(70, 5, 'Certified & Digitally Accepted (v1.0)', 0, 1, 'L');

        // Embed Signature Image
        $sigPath = $d['signature_path'] ?? '';
        if ($sigPath && !file_exists($sigPath)) {
            $check1 = realpath(__DIR__ . '/../../../' . ltrim($sigPath, '/\\'));
            $check2 = realpath(__DIR__ . '/../../' . ltrim($sigPath, '/\\'));
            if ($check1 && file_exists($check1)) {
                $sigPath = $check1;
            } elseif ($check2 && file_exists($check2)) {
                $sigPath = $check2;
            }
        }

        $this->SetXY(125, $sigBoxY + 3);
        $this->SetFont('Helvetica', 'B', 8.5);
        $this->SetTextColor(70, 70, 70);
        $this->Cell(65, 5, 'Applicant Digital Signature:', 0, 1, 'C');

        $sigRendered = false;
        if ($sigPath && file_exists($sigPath)) {
            try {
                $this->Image($sigPath, 130, $sigBoxY + 9, 55, 20);
                $sigRendered = true;
            } catch (Throwable $e) {
                $sigRendered = false;
            }
        }

        if (!$sigRendered) {
            $this->SetXY(125, $sigBoxY + 14);
            $this->SetFont('Helvetica', 'I', 8.5);
            $this->SetTextColor(100, 100, 100);
            $this->Cell(65, 5, '[Digitally Signed & Authenticated]', 0, 1, 'C');
        }

        $this->SetY($sigBoxY + 40);

        // SECTION 8: FOR OFFICIAL USE ONLY (ADMINISTRATION)
        $this->renderSectionHeader('8. For Official School Administration Use Only');
        $this->SetDrawColor(200, 210, 220);
        $this->SetFillColor(252, 252, 252);
        $offBoxY = $this->GetY();
        $this->Rect(15, $offBoxY, 180, 28, 'DF');

        $this->SetXY(18, $offBoxY + 3);
        $this->renderRow('Date of Application', $d['submitted_date'] ?? date('Y-m-d'), 'Application Form Fee', strtoupper($d['form_fee_status'] ?? 'UNPAID'));
        
        $this->SetX(18);
        $this->renderRow('Fee Receipt No.', $d['form_fee_receipt'] ?? 'Pending Office Settlement', 'Admitted On', $d['admitted_on'] ?? 'Pending Committee Decision');
        
        $this->SetX(18);
        $this->renderRow('Reported On Date', $d['reported_on'] ?? 'Pending Reporting', 'Application Status', strtoupper($d['status'] ?? 'PENDING'));

        $this->SetXY(18, $offBoxY + 21);
        $this->SetFont('Helvetica', 'B', 8);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(85, 5, 'Headteacher Signature: ___________________________', 0, 0, 'L');
        $this->Cell(85, 5, 'Official School Stamp: [                                    ]', 0, 1, 'R');
    }

    /**
     * Generate the complete Printable Blank Application Form
     * Strictly identical field structure as the online form
     */
    public function generateBlank() {
        $this->isBlank = true;
        $this->AddPage();

        // PAGE 1
        // SECTION 1: APPLICATION DETAILS
        $this->renderSectionHeader('1. Application Information');
        $this->SetFont('Helvetica', 'B', 8.5);
        $this->SetTextColor(70, 70, 70);
        $this->Cell(45, 5.8, 'Academic Year:', 0, 0, 'L');
        $this->SetFont('Helvetica', '', 8.5);
        $this->SetTextColor(150, 150, 150);
        $this->Cell(45, 5.8, '20____', 0, 0, 'L');

        $this->SetFont('Helvetica', 'B', 8.5);
        $this->SetTextColor(70, 70, 70);
        $this->Cell(45, 5.8, 'Term:', 0, 0, 'L');
        $this->SetFont('Helvetica', '', 8.5);
        $this->SetTextColor(40, 40, 40);
        $this->Cell(45, 5.8, '[  ] Term 1     [  ] Term 2     [  ] Term 3', 0, 1, 'L');

        $this->SetFont('Helvetica', 'B', 8.5);
        $this->SetTextColor(70, 70, 70);
        $this->Cell(45, 5.8, 'Class to Join:', 0, 0, 'L');
        $this->SetFont('Helvetica', '', 8.5);
        $this->SetTextColor(150, 150, 150);
        $this->Cell(45, 5.8, '................................................', 0, 0, 'L');

        $this->SetFont('Helvetica', 'B', 8.5);
        $this->SetTextColor(70, 70, 70);
        $this->Cell(45, 5.8, 'School Type:', 0, 0, 'L');
        $this->SetFont('Helvetica', '', 8.5);
        $this->SetTextColor(40, 40, 40);
        $this->Cell(45, 5.8, '[  ] Day School         [  ] Boarding', 0, 1, 'L');

        // SECTION 2: CHILD INFORMATION
        $this->renderSectionHeader('2. Child Information');
        $this->renderBlankRow("Child's Surname", "Other Names");
        
        $this->SetFont('Helvetica', 'B', 8.5);
        $this->SetTextColor(70, 70, 70);
        $this->Cell(45, 5.8, 'Date of Birth:', 0, 0, 'L');
        $this->SetFont('Helvetica', '', 8.5);
        $this->SetTextColor(150, 150, 150);
        $this->Cell(45, 5.8, 'DD / MM / YYYY', 0, 0, 'L');

        $this->SetFont('Helvetica', 'B', 8.5);
        $this->SetTextColor(70, 70, 70);
        $this->Cell(45, 5.8, 'Sex:', 0, 0, 'L');
        $this->SetFont('Helvetica', '', 8.5);
        $this->SetTextColor(40, 40, 40);
        $this->Cell(45, 5.8, '[  ] Male                   [  ] Female', 0, 1, 'L');

        $this->renderBlankRow("Religion", "Tribe");
        
        $this->SetFont('Helvetica', 'B', 8.5);
        $this->SetTextColor(70, 70, 70);
        $this->Cell(45, 5.8, 'Position in Family:', 0, 0, 'L');
        $this->SetFont('Helvetica', '', 8.5);
        $this->SetTextColor(150, 150, 150);
        $this->Cell(45, 5.8, '................................................', 0, 0, 'L');

        $this->SetFont('Helvetica', 'B', 8.5);
        $this->SetTextColor(70, 70, 70);
        $this->Cell(45, 5.8, 'Attended School Before:', 0, 0, 'L');
        $this->SetFont('Helvetica', '', 8.5);
        $this->SetTextColor(40, 40, 40);
        $this->Cell(45, 5.8, '[  ] Yes                    [  ] No', 0, 1, 'L');

        $this->renderBlankRow("Present/Previous School", "School Location");
        $this->renderBlankRow("1st Language Spoken", "2nd Language Spoken");

        // SECTION 3: PERSON RESPONSIBLE & PARENTS
        $this->renderSectionHeader('3. Person Responsible & Parents Particulars');
        $this->renderBlankRow("Person Responsible", "Telephone");
        $this->renderBlankRow("Email Address", "Postal Address");
        $this->renderBlankRow("Location & Contact Address", "Father/Mother Living Together?");

        $this->renderBlankRow("Father's Name", "Father's Working As");
        $this->renderBlankRow("Father's At/Workplace", "Father's Address");

        $this->renderBlankRow("Mother's Name", "Mother's Working As");
        $this->renderBlankRow("Mother's At/Workplace", "Mother's Address");

        // PAGE 2
        $this->AddPage();

        // SECTION 4: EMERGENCY & HEALTH
        $this->renderSectionHeader('4. Emergency & Health Information');
        $this->renderBlankRow("Next of Kin in Emergency", "Day School or Boarder");

        $this->SetFont('Helvetica', 'B', 8.5);
        $this->SetTextColor(70, 70, 70);
        $this->Cell(45, 5.8, 'If Boarder: Wets Bed?', 0, 0, 'L');
        $this->SetFont('Helvetica', '', 8.5);
        $this->SetTextColor(40, 40, 40);
        $this->Cell(45, 5.8, '[  ] Yes            [  ] No', 0, 0, 'L');

        $this->SetFont('Helvetica', 'B', 8.5);
        $this->SetTextColor(70, 70, 70);
        $this->Cell(45, 5.8, 'Had Health Handicap?', 0, 0, 'L');
        $this->SetFont('Helvetica', '', 8.5);
        $this->SetTextColor(40, 40, 40);
        $this->Cell(45, 5.8, '[  ] Yes            [  ] No', 0, 1, 'L');

        $this->renderBlankRow("Health Handicap Details");
        $this->renderBlankRow("Child's Doctor", "Doctor's Location");

        $this->SetFont('Helvetica', 'B', 8.5);
        $this->SetTextColor(70, 70, 70);
        $this->Cell(45, 5.8, 'Was Child Immunized?', 0, 0, 'L');
        $this->SetFont('Helvetica', '', 8.5);
        $this->SetTextColor(40, 40, 40);
        $this->Cell(135, 5.8, '[  ] Yes (Immunized Against Measles)         [  ] No', 0, 1, 'L');

        // SECTION 5: OTHER INFORMATION
        $this->renderSectionHeader('5. Other Information');
        $this->SetFont('Helvetica', 'B', 8.5);
        $this->SetTextColor(70, 70, 70);
        $this->Cell(180, 5, 'Any other information that might be of use to both parties:', 0, 1, 'L');
        $this->SetFont('Helvetica', '', 8.5);
        $this->SetTextColor(150, 150, 150);
        $this->Cell(180, 5.5, '....................................................................................................................................................................................', 0, 1, 'L');
        $this->Cell(180, 5.5, '....................................................................................................................................................................................', 0, 1, 'L');

        // SECTION 6: RULES & REGULATIONS
        $this->renderSectionHeader('6. School Rules & Regulations');
        $this->renderRulesList();

        // PAGE 3
        $this->AddPage();

        // SECTION 7: DECLARATION & SIGNATURE
        $this->renderSectionHeader('7. Parent / Guardian Declaration & Signature');
        
        $this->SetDrawColor(218, 225, 233);
        $this->SetFillColor(250, 252, 255);
        $ackBoxY = $this->GetY();
        $this->Rect(15, $ackBoxY, 180, 26, 'DF');

        $this->SetXY(18, $ackBoxY + 3);
        $this->SetFont('Helvetica', 'B', 8.5);
        $this->SetTextColor(11, 37, 69);
        $this->Cell(174, 5, 'OFFICIAL RULES & REGULATIONS ACKNOWLEDGEMENT:', 0, 1, 'L');
        
        $this->SetX(18);
        $this->SetFont('Helvetica', '', 8.5);
        $this->SetTextColor(40, 40, 40);
        $this->Cell(174, 5, 'I ......................................................................... Parent/Guardian of .........................................................................', 0, 1, 'L');
        $this->SetX(18);
        $this->Cell(174, 5, 'Class ................................................................', 0, 1, 'L');
        $this->SetX(18);
        $this->SetFont('Helvetica', 'B', 8.5);
        $this->Cell(174, 5, 'I have read the rules and regulations, and I am ready to abide.', 0, 1, 'L');

        $this->SetY($ackBoxY + 29);

        // Blank Signature Box
        $sigBoxY = $this->GetY();
        $this->SetDrawColor(218, 225, 233);
        $this->SetFillColor(255, 255, 255);
        $this->Rect(15, $sigBoxY, 180, 36, 'DF');

        $this->SetXY(18, $sigBoxY + 5);
        $this->SetFont('Helvetica', 'B', 8.5);
        $this->SetTextColor(70, 70, 70);
        $this->Cell(50, 6, 'Parent/Guardian Full Name:', 0, 0, 'L');
        $this->SetFont('Helvetica', '', 8.5);
        $this->SetTextColor(150, 150, 150);
        $this->Cell(120, 6, '....................................................................................................................', 0, 1, 'L');

        $this->SetXY(18, $sigBoxY + 14);
        $this->SetFont('Helvetica', 'B', 8.5);
        $this->SetTextColor(70, 70, 70);
        $this->Cell(50, 6, 'Date of Signing:', 0, 0, 'L');
        $this->SetFont('Helvetica', '', 8.5);
        $this->SetTextColor(150, 150, 150);
        $this->Cell(120, 6, 'DD / MM / 20____', 0, 1, 'L');

        $this->SetXY(18, $sigBoxY + 23);
        $this->SetFont('Helvetica', 'B', 8.5);
        $this->SetTextColor(70, 70, 70);
        $this->Cell(50, 6, "Parent's Signature:", 0, 0, 'L');
        $this->SetFont('Helvetica', '', 8.5);
        $this->SetTextColor(150, 150, 150);
        $this->Cell(120, 6, '____________________________________________________', 0, 1, 'L');

        $this->SetY($sigBoxY + 40);

        // SECTION 8: FOR OFFICIAL USE ONLY (ADMINISTRATION)
        $this->renderSectionHeader('8. For Official School Administration Use Only');
        $this->SetDrawColor(200, 210, 220);
        $this->SetFillColor(252, 252, 252);
        $offBoxY = $this->GetY();
        $this->Rect(15, $offBoxY, 180, 36, 'DF');

        $this->SetXY(18, $offBoxY + 3);
        $this->SetFont('Helvetica', 'B', 8.5);
        $this->SetTextColor(70, 70, 70);
        $this->Cell(45, 5.5, 'Date of Application:', 0, 0, 'L');
        $this->SetFont('Helvetica', '', 8.5);
        $this->SetTextColor(150, 150, 150);
        $this->Cell(45, 5.5, '____ / ____ / 20____', 0, 0, 'L');

        $this->SetFont('Helvetica', 'B', 8.5);
        $this->SetTextColor(70, 70, 70);
        $this->Cell(45, 5.5, 'Application Form Fee:', 0, 0, 'L');
        $this->SetFont('Helvetica', '', 8.5);
        $this->SetTextColor(40, 40, 40);
        $this->Cell(45, 5.5, '[  ] Paid           [  ] Unpaid', 0, 1, 'L');

        $this->SetX(18);
        $this->SetFont('Helvetica', 'B', 8.5);
        $this->SetTextColor(70, 70, 70);
        $this->Cell(45, 5.5, 'Fee Receipt No.:', 0, 0, 'L');
        $this->SetFont('Helvetica', '', 8.5);
        $this->SetTextColor(150, 150, 150);
        $this->Cell(45, 5.5, '...........................................', 0, 0, 'L');

        $this->SetFont('Helvetica', 'B', 8.5);
        $this->SetTextColor(70, 70, 70);
        $this->Cell(45, 5.5, 'Admitted On Date:', 0, 0, 'L');
        $this->SetFont('Helvetica', '', 8.5);
        $this->SetTextColor(150, 150, 150);
        $this->Cell(45, 5.5, '____ / ____ / 20____', 0, 1, 'L');

        $this->SetX(18);
        $this->SetFont('Helvetica', 'B', 8.5);
        $this->SetTextColor(70, 70, 70);
        $this->Cell(45, 5.5, 'Reported On Date:', 0, 0, 'L');
        $this->SetFont('Helvetica', '', 8.5);
        $this->SetTextColor(150, 150, 150);
        $this->Cell(45, 5.5, '____ / ____ / 20____', 0, 0, 'L');

        $this->SetFont('Helvetica', 'B', 8.5);
        $this->SetTextColor(70, 70, 70);
        $this->Cell(45, 5.5, 'Application Status:', 0, 0, 'L');
        $this->SetFont('Helvetica', '', 8.5);
        $this->SetTextColor(40, 40, 40);
        $this->Cell(45, 5.5, '[  ] Admitted     [  ] Waitlist', 0, 1, 'L');

        $this->SetXY(18, $offBoxY + 27);
        $this->SetFont('Helvetica', 'B', 8);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(85, 5, 'Headteacher Signature: ___________________________', 0, 0, 'L');
        $this->Cell(85, 5, 'Official School Stamp: [                                    ]', 0, 1, 'R');
    }
}
