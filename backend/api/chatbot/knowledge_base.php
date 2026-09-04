<?php
/**
 * St. Lawrence School AI Assistant - Knowledge Base
 * Phase 2.1: Authoritative School Fee Structure & Exact Class Mapping
 * 100% free, deterministic, and self-contained (zero external LLM/API dependencies).
 */

class KnowledgeBase {
    
    // Authoritative Official School Fee Structure (Source of Truth)
    public const OFFICIAL_FEES = [
        'nursery' => [
            'name' => 'Nursery (Baby–Top Class)',
            'day_scholar' => 'UGX 474,000',
            'boarding' => 'UGX 894,000',
            'period' => 'per term'
        ],
        'p1_p5' => [
            'name' => 'P1–P5',
            'day_scholar' => 'UGX 579,000',
            'boarding' => 'UGX 1,019,000',
            'period' => 'per term'
        ],
        'p6_p7' => [
            'name' => 'P6–P7',
            'day_scholar' => 'UGX 629,000',
            'boarding' => 'UGX 1,094,000',
            'period' => 'per term'
        ]
    ];
    
    private $knowledge = [];
    private $stopWords = [];
    private $outOfScopeKeywords = [];
    
    public function __construct() {
        $this->buildStopWords();
        $this->buildOutOfScopeKeywords();
        $this->buildKnowledgeBase();
    }
    
    private function buildStopWords() {
        $this->stopWords = [
            'what', 'is', 'the', 'a', 'an', 'do', 'you', 'have', 'can', 'i', 
            'of', 'in', 'to', 'for', 'on', 'at', 'it', 'this', 'that', 'there', 
            'about', 'tell', 'me', 'how', 'much', 'does', 'we', 'are', 'your', 
            'our', 'and', 'or', 'be', 'so', 'my', 'please', 'would', 'like',
            'know', 'any', 'some', 'with', 'by', 'as', 'if', 'from', 'who', 'which',
            'just', 'then', 'also', 'get', 'give'
        ];
    }
    
    private function buildOutOfScopeKeywords() {
        $this->outOfScopeKeywords = [
            'weather', 'forecast', 'rain', 'temperature', 'sunny', 'climate',
            'premier league', 'arsenal', 'manchester', 'chelsea', 'liverpool', 'champions league',
            'bitcoin', 'crypto', 'cryptocurrency', 'forex', 'stocks', 'trading',
            'recipe', 'cooking recipe', 'cook food at home', 'movie tickets', 'cinema',
            'president of america', 'ukraine', 'russia', 'politics', 'election results'
        ];
    }
    
    private function buildKnowledgeBase() {
        
        // GREETING
        $this->knowledge['greeting'] = [
            'topic' => 'greeting',
            'keywords' => [
                'hello', 'hi', 'hey', 'good morning', 'good afternoon', 'good evening',
                'greetings', 'habari', 'anyone there', 'start'
            ],
            'response' => "Hello! 👋 Welcome to St. Lawrence Junior School - Kabowa. I am St. Lawrence Assistant.\n\nI can help you with details about our **programmes**, **school fees**, **admissions**, **boarding**, and **location**. What would you like to know?",
            'suggestions' => [
                "What programmes do you offer?",
                "How much are the school fees?",
                "Do you offer boarding?",
                "How do I apply?"
            ]
        ];
        
        // SCHOOL OVERVIEW / ABOUT
        $this->knowledge['school_info'] = [
            'topic' => 'school_info',
            'keywords' => [
                'about school', 'about st lawrence', 'who are you', 'tell me about school',
                'history', 'founded', 'established', 'years of experience', 'school motto',
                'background', 'tell me about st lawrence', 'overview', 'information about school'
            ],
            'response' => "🏫 **St. Lawrence Junior School - Kabowa** is a premier private mixed day and boarding primary school established in **2010** (over 14 years of academic excellence).\n\n• **Motto:** *\"We Strive to Excel\"*\n• **Director:** Mr. Kimera Emmanuel\n• **Location:** St. Lawrence Junior School Kabowa, 2 Gabunga Road, Kampala, Uganda\n• **Sections:** Nursery (Baby, Middle, Top) and Primary (P.1 – P.7)\n\nWould you like to explore our academic programmes or check admission requirements?",
            'suggestions' => [
                "What programmes do you offer?",
                "How do I apply?",
                "Where is the school located?"
            ]
        ];
        
        // DIRECTOR & LEADERSHIP
        $this->knowledge['director'] = [
            'topic' => 'director',
            'keywords' => [
                'director', 'mr kimera', 'kimera emmanuel', 'headteacher', 'principal',
                'who is in charge', 'school head', 'school leader', 'management', 'who leads the school',
                'director name', 'who is director'
            ],
            'response' => "👨‍💼 **School Leadership:**\n\nSt. Lawrence Junior School Kabowa is under the visionary directorship of **Mr. Kimera Emmanuel**, supported by an experienced Headteacher, Director of Studies, and senior management team.\n\nSince 2010, this leadership has guided our pupils to consistent First Grade PLE success. To book an appointment with the administration, please call **+256 701 420 506**.",
            'suggestions' => [
                "Contact Information",
                "School Location",
                "Visit the School"
            ]
        ];
        
        // 4 CORE PROGRAMMES OVERVIEW
        $this->knowledge['programs'] = [
            'topic' => 'programs',
            'keywords' => [
                'programmes', 'programs', 'what programmes do you offer', 'what do you offer',
                'classes offered', 'levels', 'sections', 'grades', 'what courses',
                'which classes', 'academic programs', 'all programs', 'tell me about programs'
            ],
            'response' => "📚 **We offer 4 distinct educational programmes:**\n\n1. **🎨 Nursery Section:** Early learning for Baby, Middle, and Top Class (ages 3–5).\n2. **📖 Primary Section:** Primary 1 to Primary 7, following the national UNEB curriculum with top PLE results.\n3. **☀️ Day School:** Structured daily learning with hot lunch and school bus options.\n4. **🏠 Boarding School:** 24/7 care with modern dormitories and supervised evening study.\n\nWhich of these programmes would you like to explore?",
            'suggestions' => [
                "Tell me about Nursery",
                "Tell me about Primary",
                "What about Day School?",
                "Do you offer boarding?"
            ]
        ];
        
        // PROGRAM 1: NURSERY
        $this->knowledge['program_nursery'] = [
            'topic' => 'nursery',
            'keywords' => [
                'nursery', 'kindergarten', 'baby class', 'middle class', 'top class',
                'early childhood', 'pre-primary', 'pre primary', 'toddler', 'daycare',
                'tell me about nursery', 'what about nursery', 'nursery section', 'nursery class',
                'first programme', 'the first one', 'first one'
            ],
            'response' => "🎨 **Nursery Section (Ages 3 to 5):**\n\n• **Baby Class (Age 3):** Play-based learning, social adjustment, and sensory development.\n• **Middle Class (Age 4):** Pre-literacy, handwriting foundations, and early number concepts.\n• **Top Class (Age 5):** Phonics mastery, early reading, and preparation for Primary 1.\n\nWe provide small class ratios (1:15) with dedicated assistant teachers. Would you like to know about Nursery fees or admissions?",
            'suggestions' => [
                "How much is Nursery?",
                "How do I apply?",
                "Does it include lunch?"
            ]
        ];
        
        // PROGRAM 2: PRIMARY
        $this->knowledge['program_primary'] = [
            'topic' => 'primary',
            'keywords' => [
                'primary', 'primary section', 'uneb', 'ple', 'primary curriculum',
                'subjects in primary', 'primary school', 'tell me about primary',
                'what about primary', 'second programme', 'the second one'
            ],
            'response' => "📖 **Primary Section (Primary 1 – Primary 7):**\n\n• **Lower Primary (P.1–P.3):** Reading fluency, foundational mathematics, integrated science, and computer literacy.\n• **Upper Primary (P.4–P.7):** Subject specialization (Math, English, Science, Social Studies), life skills, and French/Luganda.\n• **Candidate Classes (P.7):** Intensive PLE preparation, seminars, and mock exams yielding over 95% First & Second Grades annually.\n\nWould you like to know about Primary school fees or admissions?",
            'suggestions' => [
                "How much is Primary 1?",
                "How much is Primary 6?",
                "How do I apply?"
            ]
        ];
        
        // PROGRAM 3: DAY SCHOOL
        $this->knowledge['program_day'] = [
            'topic' => 'day_school',
            'keywords' => [
                'day school', 'day scholar', 'day schooling', 'day pupils', 'day student',
                'non boarding', 'day program', 'commute', 'day classes', 'what about day school',
                'tell me about day school', 'third programme', 'the third one'
            ],
            'response' => "☀️ **Day School Programme:**\n\n• **School Hours:** 7:00 AM – 5:00 PM (Monday – Friday).\n• **Nutritious Meals:** Every day scholar receives a morning snack with tea/porridge and a wholesome hot lunch on campus.\n• **School Transport:** Supervised bus routes cover Kabowa, Rubaga, Nateete, Busega, and Kampala Central.\n\nWould you like to see Day Scholar fees or transport route details?",
            'suggestions' => [
                "Day Scholar Fees",
                "Does it include lunch?",
                "School Bus Routes"
            ]
        ];
        
        // PROGRAM 4: BOARDING
        $this->knowledge['program_boarding'] = [
            'topic' => 'boarding',
            'keywords' => [
                'boarding', 'boarder', 'boarding school', 'do you offer boarding', 'is boarding available',
                'does the school offer boarding', 'can my child stay at school', 'can children live at school',
                'do you accommodate boarders', 'is there a boarding section', 'dormitory', 'hostel',
                'residential', 'boarding life', 'boarding facilities', 'sleep at school', 'stay overnight',
                'fourth programme', 'the fourth one', 'the last one', 'last one'
            ],
            'response' => "🏠 **Boarding School Programme:**\n\nYes! We provide full, secure residential boarding for boys and girls:\n\n• **Dormitories:** Separate, modern houses with dedicated bed spaces and 24/7 security.\n• **Welfare & Care:** Experienced resident matrons, boarding masters, and a registered nurse in our campus sick bay.\n• **Meals & Academics:** 5 balanced meals daily and supervised evening study preps to cultivate discipline.\n\nWould you like to know the boarding fees or the admission steps?",
            'suggestions' => [
                "Boarding Fees",
                "How do I apply?",
                "What are the requirements?"
            ]
        ];
        
        // GENERIC SCHOOL FEES (COMPLETE STRUCTURE)
        $this->knowledge['fees_complete'] = [
            'topic' => 'fees',
            'keywords' => [
                'fees', 'school fees', 'how much are the school fees', 'what are the school fees',
                'what are your fees', 'fee structure', 'all fees', 'tuition fees', 'how much does school cost',
                'what do i pay per term', 'what is the termly fee', 'how expensive is the school',
                'cost per term', 'school charges', 'tuition', 'how much are fees', 'what are fees'
            ],
            'response' => "💰 **School fees per term:**\n\n• **Nursery (Baby–Top Class):** UGX 474,000 Day Scholar / UGX 894,000 Boarding\n• **P1–P5:** UGX 579,000 Day Scholar / UGX 1,019,000 Boarding\n• **P6–P7:** UGX 629,000 Day Scholar / UGX 1,094,000 Boarding\n\nWould you like me to help you with a specific class?",
            'suggestions' => [
                "How much is Nursery?",
                "How much is Primary 1?",
                "How much is Primary 6?",
                "Uniform Prices"
            ]
        ];
        
        // FEES - DAY SCHOLARS ONLY
        $this->knowledge['fees_day'] = [
            'topic' => 'day_fees',
            'keywords' => [
                'day fees', 'day scholar fees', 'day student fees', 'cost for day scholar',
                'day tuition', 'how much for day school', 'day scholar cost', 'day pricing',
                'day school fees'
            ],
            'response' => "💰 **Official Day Scholar Fees (Per Term):**\n\n• **Nursery (Baby–Top Class):** UGX 474,000\n• **P1–P5:** UGX 579,000\n• **P6–P7:** UGX 629,000\n\nAll day scholar fees are per term and include tuition, mid-morning snack with tea/porridge, hot lunch, and learning materials. Would you like me to help you with a specific class?",
            'suggestions' => [
                "Boarding Fees",
                "Uniform Prices",
                "How do I apply?"
            ]
        ];
        
        // FEES - BOARDING ONLY
        $this->knowledge['fees_boarding'] = [
            'topic' => 'boarding_fees',
            'keywords' => [
                'boarding fees', 'cost of boarding', 'how much for boarding', 'boarding charges',
                'boarding tuition', 'boarder fees', 'how much do boarders pay', 'boarding cost',
                'what do i pay for boarding', 'boarding price', 'boarding school fees'
            ],
            'response' => "💰 **Official Boarding Fees (Per Term):**\n\n• **Nursery (Baby–Top Class):** UGX 894,000\n• **P1–P5:** UGX 1,019,000\n• **P6–P7:** UGX 1,094,000\n\nAll boarding fees are per term and include full accommodation, 5 nutritious meals daily, 24/7 matron & security supervision, sick bay medical care, and evening study prep. Would you like me to help you with a specific class?",
            'suggestions' => [
                "Day Scholar Fees",
                "What are the requirements?",
                "How do I apply?"
            ]
        ];
        
        // UNIFORMS
        $this->knowledge['uniforms'] = [
            'topic' => 'uniforms',
            'keywords' => [
                'uniform', 'uniforms', 'dress code', 'attire', 'clothes', 'sweater',
                'shirt', 'uniform price', 'uniform cost', 'how much is uniform', 'school clothes'
            ],
            'response' => "👔 **School Uniform Prices (One-off purchase):**\n\n**Day Scholars (Boys):**\n• Nursery – P.2: UGX 200,000 | P.3 – P.5: UGX 240,000 | P.6 – P.7: UGX 300,000\n\n**Day Scholars (Girls):**\n• Nursery – P.2: UGX 190,000 | P.3 – P.5: UGX 230,000 | P.6 – P.7: UGX 270,000\n\nUniforms include shirts/blouses, shorts/skirts, sweater, stockings, and tie. Boarding sets include additional laundry pairs. Available at the school office.",
            'suggestions' => [
                "How much are the school fees?",
                "How do I apply?",
                "Contact Office"
            ]
        ];
        
        // ADMISSION PROCESS
        $this->knowledge['admission'] = [
            'topic' => 'admission',
            'keywords' => [
                'admission', 'apply', 'enroll', 'join', 'register', 'application',
                'how do i apply', 'how can i enroll my child', 'what is the admission process',
                'how do i register my child', 'what do i need to join the school',
                'how can i get admission', 'admissions process', 'how to enroll', 'join the school'
            ],
            'response' => "📝 **Admissions at St. Lawrence Junior School (6 Simple Steps):**\n\n1. **Visit the School:** Tour our campus at 2 Gabunga Road, Kampala, Uganda.\n2. **Pick Application Form:** Obtain it at the office or download online.\n3. **Submit Documents:** Birth certificate, 2 passport photos, previous report, immunization card.\n4. **Diagnostic Assessment:** A friendly interaction to place your child.\n5. **Receive Admission Letter:** Formal offer of placement.\n6. **Fee Payment & Enrolment:** Settle fees with the bursar to confirm registration.\n\nWould you like to know the exact documents needed or contact the admissions desk?",
            'suggestions' => [
                "What do I need?",
                "How much are the school fees?",
                "Schedule a Visit"
            ]
        ];
        
        // ADMISSION REQUIREMENTS (DOCUMENTS)
        $this->knowledge['admission_requirements'] = [
            'topic' => 'admission',
            'keywords' => [
                'what do i need', 'what documents', 'admission requirements', 'requirements to join',
                'requirements for admission', 'documents needed', 'papers needed', 'what to bring'
            ],
            'response' => "📋 **Required Admission Documents:**\n\nTo enroll your child at St. Lawrence Junior School Kabowa, please bring:\n\n1. Copy of the child's **Birth Certificate**\n2. Two (2) recent **Passport-sized Photographs** of the child\n3. Previous school **Report Card / Transfer Letter** (for P.2 to P.7)\n4. Copy of the child's **Immunization Card / Medical Record**\n\nOnce documents are submitted, a short placement assessment is conducted. Would you like to check school fees or book a visit?",
            'suggestions' => [
                "How much are the school fees?",
                "Visit the School",
                "Contact Admissions"
            ]
        ];
        
        // MEALS & NUTRITION
        $this->knowledge['meals'] = [
            'topic' => 'meals',
            'keywords' => [
                'meals', 'food', 'lunch', 'breakfast', 'diet', 'nutrition', 'dining',
                'does it include lunch', 'is lunch included', 'is food provided',
                'what do they eat', 'feeding', 'lunch included', 'break tea'
            ],
            'response' => "🍽️ **Meals & Nutrition:**\n\nYes! Nutritious, well-balanced meals are prepared fresh daily in our hygienic kitchen:\n\n• **Day Scholars:** Receive a mid-morning break snack with tea/porridge, plus a wholesome hot lunch (posho, rice, matooke, beans, meat, and fresh vegetables).\n• **Boarding Students:** Receive **5 meals daily** (breakfast, morning snack, lunch, 4:00 PM evening tea, and dinner).\n\nDietary restrictions and allergies are carefully catered for upon parent notice.",
            'suggestions' => [
                "Day School Programme",
                "Boarding Programme",
                "How much are the school fees?"
            ]
        ];
        
        // LOCATION & ADDRESS
        $this->knowledge['location'] = [
            'topic' => 'location',
            'keywords' => [
                'where is the school', 'where are you located', 'what is your address',
                'where can i find you', 'where located', 'location', 'address', 'where are you',
                'situated', 'find you', 'where is st lawrence', 'where exactly is st lawrence junior school',
                'where exactly is st lawrence junior school kabowa', 'where exactly is st lawrence',
                'where is the school located', 'where exactly is the school', 'school location',
                'school address', '2 gabunga road', 'gabunga road', 'gabunga', 'plus code',
                'whats the plus code', 'what is the plus code', 'send me the location',
                'send me the school location', 'how can i find the school', 'where is the campus',
                'google maps', 'google map', 'map', 'where are you found', 'where is kabowa'
            ],
            'response' => "📍 **Official School Location:**\n\n**St. Lawrence Junior School Kabowa**\n**Address:** 2 Gabunga Road, Kampala, Uganda\n• **Plus Code:** 7HJ5+MX Kampala\n• **Google Maps:** https://maps.app.goo.gl/k2jE4X8KgkgZL4jn7\n• **Phone:** +256 772 420 506 / +256 701 420 506\n\nVisitors and parents are welcome during school office hours (Monday – Friday, 8:00 AM – 4:00 PM).",
            'suggestions' => [
                "How do I get there?",
                "Contact Numbers",
                "School Hours",
                "How do I apply?"
            ]
        ];
        
        // DETAILED DIRECTIONS & NAVIGATION
        $this->knowledge['location_detailed'] = [
            'topic' => 'location',
            'keywords' => [
                'how do i get there', 'how do i reach the school', 'directions', 'how can i reach',
                'how to get there', 'directions please', 'driving directions', 'can you give me directions',
                'give me directions', 'how do i get to the school', 'route to school', 'getting there',
                'how do i reach', 'how can i get there', 'navigation', 'map link', 'directions to school'
            ],
            'response' => "🚗 **Directions to St. Lawrence Junior School Kabowa:**\n\n**Address:** 2 Gabunga Road, Kampala, Uganda\n• **Google Maps:** https://maps.app.goo.gl/k2jE4X8KgkgZL4jn7\n• **Plus Code:** 7HJ5+MX Kampala\n• **Phone Assistance:** +256 772 420 506 / +256 701 420 506\n\nFor turn-by-turn driving or walking directions, open the Google Maps link above on your phone or computer.",
            'suggestions' => [
                "Where is the school?",
                "Contact Numbers",
                "School Hours",
                "Schedule a Visit"
            ]
        ];
        
        // CONTACT INFORMATION
        $this->knowledge['contact'] = [
            'topic' => 'contact',
            'keywords' => [
                'contact', 'phone', 'telephone', 'call', 'reach', 'mobile', 'email',
                'email address', 'contact number', 'phone number', 'hotline', 'how to contact',
                'what is the schools phone number', 'what is the school phone number',
                'what is your phone number', 'school telephone', 'school phone', 'phone numbers'
            ],
            'response' => "📞 **Official Contact Details:**\n\n• **Phone 1:** +256 772 420 506\n• **Phone 2:** +256 701 420 506\n• **Address:** St. Lawrence Junior School Kabowa, 2 Gabunga Road, Kampala, Uganda\n• **Plus Code:** 7HJ5+MX Kampala\n• **Google Maps:** https://maps.app.goo.gl/k2jE4X8KgkgZL4jn7\n• **Email:** stlawrencejuniorschoolkabowa@gmail.com\n• **Alternative Email:** st.lawrence.juniorschool@yahoo.com\n\nOffice hours are **Monday to Friday (8:00 AM – 4:00 PM)** and **Saturday (9:00 AM – 12:00 PM)**.",
            'suggestions' => [
                "Where is the school?",
                "How do I get there?",
                "School Hours",
                "How do I apply?"
            ]
        ];
        
        // SCHOOL HOURS
        $this->knowledge['hours'] = [
            'topic' => 'hours',
            'keywords' => [
                'hours', 'time', 'opening time', 'closing time', 'schedule',
                'when do you open', 'operating hours', 'office hours', 'school time',
                'what time does school start', 'what time does school close'
            ],
            'response' => "⏰ **School & Office Hours:**\n\n• **Classes (Mon – Fri):** 7:00 AM – 4:30 PM (Pick-up until 5:00 PM)\n• **Saturday:** 8:00 AM – 1:00 PM (Sports, co-curriculars & remedial sessions)\n• **Sunday:** Closed (Boarding pupils follow supervised weekend routines)\n• **Administration & Bursar:** Monday – Friday, 8:00 AM – 4:00 PM.\n\nBoarding pupils receive 24/7 staff supervision.",
            'suggestions' => [
                "What programmes do you offer?",
                "How much are the school fees?",
                "Where is the school?"
            ]
        ];
        
        // TRANSPORT & BUS SERVICE
        $this->knowledge['transport'] = [
            'topic' => 'transport',
            'keywords' => [
                'transport', 'bus', 'school bus', 'van', 'pick up', 'drop off',
                'shuttle', 'transportation', 'bus routes', 'bus fees', 'school transport'
            ],
            'response' => "🚌 **School Bus Transport Service:**\n\nWe provide reliable and supervised morning pick-up and afternoon drop-off:\n\n• **Coverage:** Kabowa, Rubaga, Nateete, Busega, Kampala Central, Mengo, Mutundwe, and neighboring areas.\n• **Safety:** Experienced licensed drivers, bus conductors on each vehicle, and seat belts fitted.\n• **Pricing:** Fees depend on your specific residential route. Contact our transport coordinator at **+256 701 420 506**.",
            'suggestions' => [
                "Day School Programme",
                "Contact Office",
                "How much are the school fees?"
            ]
        ];
        
        // ACADEMIC PERFORMANCE & PLE RESULTS
        $this->knowledge['academic_performance'] = [
            'topic' => 'academics',
            'keywords' => [
                'ple results', 'results', 'academic performance', 'pass rate',
                'exam results', 'grades', 'uneb results', 'first grade', 'performance in ple'
            ],
            'response' => "📊 **Academic Excellence & PLE Performance:**\n\n• **95%+ First and Second Grade** pass rate consistently achieved in Primary Leaving Examinations (PLE).\n• Our alumni regularly join top secondary institutions like Mt. St. Mary's Namagunga, King's College Budo, St. Mary's College Kisubi, and Gayaza High School.\n• Small class sizes ensure personalized coaching for every learner.",
            'suggestions' => [
                "Tell me about Primary",
                "How do I apply?",
                "How much are the school fees?"
            ]
        ];
        
        // EXTRACURRICULAR ACTIVITIES
        $this->knowledge['activities'] = [
            'topic' => 'activities',
            'keywords' => [
                'activities', 'extracurricular', 'sports', 'games', 'clubs',
                'football', 'netball', 'swimming', 'music', 'dance', 'drama', 'debate'
            ],
            'response' => "🎯 **Extracurricular Activities & Sports:**\n\nWe provide holistic talent development:\n\n• **Sports:** Football, netball, basketball, athletics track, and swimming.\n• **Performing Arts:** Music, traditional dance, choir, and drama festival presentations.\n• **Clubs:** Debate Club, Science & STEM Club, ICT Club, and Reading Club.\n\nSessions take place every Wednesday afternoon and Saturday morning!",
            'suggestions' => [
                "What programmes do you offer?",
                "Visit the School"
            ]
        ];
        
        // FACILITIES
        $this->knowledge['facilities'] = [
            'topic' => 'facilities',
            'keywords' => [
                'facilities', 'infrastructure', 'buildings', 'library', 'computer lab',
                'science lab', 'playground', 'campus', 'amenities'
            ],
            'response' => "🏫 **School Facilities:**\n\n• **Library:** Over 2,000 books and reading resource centers.\n• **Computer Lab:** 30+ internet-connected computers for practical ICT learning.\n• **Science Lab:** Modern practical science equipment.\n• **Sports Complex:** Full football pitch, netball courts, and nursery playground.\n• **Boarding Houses:** Separate secure dormitories with matrons.\n• **Sick Bay:** Equipped clinic with a qualified resident nurse.",
            'suggestions' => [
                "Do you offer boarding?",
                "What programmes do you offer?",
                "Schedule a Tour"
            ]
        ];
        
        // TERM DATES & CALENDAR
        $this->knowledge['term_dates'] = [
            'topic' => 'calendar',
            'keywords' => [
                'term dates', 'calendar', 'when does term start', 'academic calendar',
                'holidays', 'when do you open', 'opening date', 'closing date', 'school calendar'
            ],
            'response' => "📅 **Academic Calendar (3 Terms):**\n\n• **Term 1:** February – April\n• **Term 2:** May – August\n• **Term 3:** September – November / December\n\nExact term opening dates and event schedules are available on our website calendar or from the school administration at **+256 701 420 506**.",
            'suggestions' => [
                "How do I apply?",
                "How much are the school fees?",
                "School Hours"
            ]
        ];
        
        // VISIT THE SCHOOL
        $this->knowledge['visit'] = [
            'topic' => 'visit',
            'keywords' => [
                'visit', 'tour', 'come to school', 'schedule visit', 'see the school',
                'open day', 'school tour', 'can i visit'
            ],
            'response' => "🏫 **Visit St. Lawrence Junior School Kabowa:**\n\nYou are warmly welcome to visit us!\n\n• **Address:** 2 Gabunga Road, Kampala, Uganda\n• **Best Times:** Monday – Friday (9:00 AM – 3:00 PM) & Saturday (9:00 AM – 12:00 PM).\n• **What You See:** Classrooms, computer lab, dormitories, sports fields, and meet the teachers.\n\nPlease feel free to drop in or call **+256 772 420 506 / +256 701 420 506** to arrange a guided tour!",
            'suggestions' => [
                "Where is the school?",
                "How do I get there?",
                "How do I apply?"
            ]
        ];
        
        // SCHOOL POLICIES
        $this->knowledge['policies'] = [
            'topic' => 'policies',
            'keywords' => [
                'policy', 'policies', 'rules', 'regulations', 'discipline', 'punishment',
                'code of conduct', 'school rules'
            ],
            'response' => "📋 **School Policies & Care Standards:**\n\n• **Positive Discipline:** Zero tolerance for corporal punishment. We practice counseling and positive character guidance.\n• **Safety:** Fully fenced campus with 24/7 security guards and controlled visitor access.\n• **Punctuality:** Day scholars arrive by 7:30 AM in smart, clean uniform.\n• **Child Protection:** All staff are strictly vetted with clear child protection protocols.",
            'suggestions' => [
                "Uniform Prices",
                "School Hours",
                "Boarding Programme"
            ]
        ];
        
        // THANK YOU
        $this->knowledge['thanks'] = [
            'topic' => 'thanks',
            'keywords' => [
                'thank you', 'thanks', 'appreciate', 'thank you so much', 'helpful', 'great thanks'
            ],
            'response' => "You're very welcome! 😊 St. Lawrence Junior School Kabowa is always ready to support your child's education. Feel free to ask anything else or call us at **+256 701 420 506**.",
            'suggestions' => [
                "What programmes do you offer?",
                "How much are the school fees?",
                "How do I apply?"
            ]
        ];
        
        // GOODBYE
        $this->knowledge['goodbye'] = [
            'topic' => 'goodbye',
            'keywords' => [
                'bye', 'goodbye', 'see you', 'have a good day', 'good night', 'farewell'
            ],
            'response' => "Goodbye! 👋 Thank you for inquiring about St. Lawrence Junior School - Kabowa. *\"We Strive to Excel.\"* We hope to welcome your family soon!",
            'suggestions' => [
                "What programmes do you offer?",
                "How do I apply?"
            ]
        ];
    }
    
    /**
     * Finds the best response using conversational intent matching, entity extraction,
     * multi-turn context resolution, ordinal mapping, ambiguity checks, and anti-hallucination guardrails.
     *
     * @param string $question
     * @param array $context Context from previous turns ['active_topic' => ..., 'last_category' => ..., 'active_class' => ...]
     * @return array
     */
    public function findAnswer($question, $context = []) {
        $rawQuestion = trim($question);
        $cleanQuestion = strtolower($rawQuestion);
        
        // Normalize: lowercase, remove special characters except hyphens/alphanumerics
        $normalized = preg_replace('/[^\w\s\-]/u', ' ', $cleanQuestion);
        $normalized = preg_replace('/\s+/', ' ', trim($normalized));
        
        if (empty($normalized)) {
            return [
                'found' => false,
                'response' => "I'm here to help! What would you like to know about St. Lawrence Junior School? You can ask about our programmes, school fees, admissions, or boarding.",
                'category' => 'empty',
                'active_topic' => $context['active_topic'] ?? 'general',
                'suggestions' => $this->getQuickActions()
            ];
        }
        
        // 1. OUT-OF-SCOPE FILTER
        // Caught immediately to prevent false-positive matching
        foreach ($this->outOfScopeKeywords as $badKeyword) {
            if (strpos($cleanQuestion, $badKeyword) !== false) {
                return [
                    'found' => true,
                    'response' => "I am the St. Lawrence Junior School virtual assistant, so I specialize in school-related information such as admissions, fees, academic programmes, boarding, and school events. I am unable to assist with weather forecasts or topics outside our school.\n\nHow may I help you regarding St. Lawrence Junior School - Kabowa?",
                    'category' => 'out_of_scope',
                    'active_topic' => $context['active_topic'] ?? 'general',
                    'suggestions' => $this->getQuickActions()
                ];
            }
        }
        
        // 2. CHECK MULTI-TURN ANAPHORA, ORDINALS & CONTEXTUAL FOLLOW-UPS
        $contextualMatch = $this->resolveContextualFollowUp($cleanQuestion, $normalized, $context);
        if ($contextualMatch !== null) {
            return $contextualMatch;
        }
        
        // 3. CLASS-SPECIFIC FEE QUERIES (Authoritative Official Mapping)
        $classFeeMatch = $this->resolveClassFeeQuery($cleanQuestion, $normalized);
        if ($classFeeMatch !== null) {
            return $classFeeMatch;
        }
        
        // 4. GENERIC SCHOOL FEE INQUIRIES (Direct Concise Structure without Unnecessary Clarification)
        // Matches "How much are the school fees?", "What are your fees?", "School fees", "Fee structure", etc.
        if ($this->isGenericFeeInquiry($cleanQuestion)) {
            return [
                'found' => true,
                'response' => $this->knowledge['fees_complete']['response'],
                'category' => 'fees_complete',
                'active_topic' => 'fees',
                'suggestions' => $this->knowledge['fees_complete']['suggestions']
            ];
        }
        
        // 5. TRULY AMBIGUOUS COST QUERY (No context established)
        // Only triggers if the question is a bare contextless pronoun like "How much is it?" or "How much does it cost?"
        $isBareCostQuery = preg_match('/^(how\s+much(\s+is\s+it|\s+does\s+it\s+cost|\s+is\s+that)?\??|cost\??|pricing\??|how\s+expensive\??)$/i', trim($cleanQuestion));
        $activeTopic = $context['active_topic'] ?? 'general';
        if ($isBareCostQuery && in_array($activeTopic, ['general', 'unknown', 'greeting', 'thanks', 'goodbye', ''])) {
            return [
                'found' => true,
                'response' => "I'd be happy to help. Are you asking about Nursery, Primary, Day School or Boarding fees?",
                'category' => 'fees_clarification',
                'active_topic' => 'fees',
                'suggestions' => [
                    "Nursery Fees",
                    "Primary School Fees",
                    "Day Scholar Fees",
                    "Boarding Fees"
                ]
            ];
        }
        
        // 6. CLASS MENTION / TOPIC TRACKING (e.g. "Tell me about Primary 6", "My child is joining Primary 6")
        $classMentionMatch = $this->resolveClassMention($cleanQuestion);
        if ($classMentionMatch !== null) {
            return $classMentionMatch;
        }
        
        // 7. SECONDARY LEVEL CLARIFICATION / UNMAPPED LEVEL (Never Guess a Fee)
        if (preg_match('/\b(senior|s\.?1|s\.?2|s\.?3|s\.?4|s\.?5|s\.?6|secondary|o\s*level|a\s*level|high\s*school|grade\s*8|grade\s*9)\b/i', $cleanQuestion)) {
            return [
                'found' => true,
                'response' => "I don't have a specific fee figure for that category. The official fee structure covers Nursery, P1–P5 and P6–P7. St. Lawrence Junior School Kabowa is strictly a **Nursery and Primary school** (Baby Class through Primary 7). Please contact the school at **+256 701 420 506** for clarification.",
                'category' => 'level_clarification',
                'active_topic' => 'programs',
                'suggestions' => [
                    "How much is Primary 1?",
                    "How much is Primary 6?",
                    "How much are the school fees?"
                ]
            ];
        }

        // 8. SCORE-BASED MATCHING WITH STOP-WORD FILTERING
        $qWords = array_values(array_filter(explode(' ', $normalized), function($w) {
            return strlen($w) > 1 && !in_array($w, $this->stopWords);
        }));
        
        $matches = [];
        
        foreach ($this->knowledge as $key => $data) {
            $score = 0;
            
            foreach ($data['keywords'] as $keyword) {
                $kw = strtolower($keyword);
                
                // Exact full match
                if ($cleanQuestion === $kw || $normalized === $kw) {
                    $score += 150;
                    continue;
                }
                
                // Full phrase match within the question
                if (strpos($cleanQuestion, $kw) !== false) {
                    $kwWordCount = count(explode(' ', $kw));
                    $score += 45 * $kwWordCount;
                }
                
                // Token overlap with keyword
                $kwWords = array_values(array_filter(explode(' ', $kw), function($w) {
                    return strlen($w) > 1 && !in_array($w, $this->stopWords);
                }));
                
                $commonWords = array_intersect($qWords, $kwWords);
                if (!empty($commonWords)) {
                    $score += count($commonWords) * 15;
                }
            }
            
            // Domain entity boosting
            $score += $this->calculateEntityBoost($key, $cleanQuestion);
            
            if ($score > 0) {
                $matches[$key] = [
                    'score' => $score,
                    'response' => $data['response'],
                    'category' => $key,
                    'topic' => $data['topic'] ?? $key,
                    'suggestions' => $data['suggestions'] ?? $this->getQuickActions()
                ];
            }
        }
        
        if (!empty($matches)) {
            uasort($matches, function($a, $b) {
                return $b['score'] - $a['score'];
            });
            
            $bestMatch = reset($matches);
            
            // Minimum confidence threshold
            if ($bestMatch['score'] >= 25) {
                return [
                    'found' => true,
                    'response' => $bestMatch['response'],
                    'category' => $bestMatch['category'],
                    'active_topic' => $bestMatch['topic'],
                    'suggestions' => $bestMatch['suggestions']
                ];
            }
        }
        
        // 9. HELPFUL RECEPTIONIST FALLBACK
        return [
            'found' => false,
            'response' => "I can help with St. Lawrence Junior School information such as our programmes (Nursery, Primary, Day & Boarding), admissions, school fees, academics, calendar, and directions.\n\nWhat would you like to know, or you can contact our office directly at **+256 701 420 506**?",
            'category' => 'unknown',
            'active_topic' => $context['active_topic'] ?? 'unknown',
            'suggestions' => $this->getQuickActions()
        ];
    }
    
    /**
     * Resolves direct class fee queries with authoritative wording.
     */
    private function resolveClassFeeQuery($cleanQuestion, $normalized) {
        $isCostQuery = preg_match('/\b(how\s+much|cost|fee|fees|pay|tuition|price|charges|rate)\b/i', $cleanQuestion);
        if (!$isCostQuery) {
            return null;
        }
        
        // NURSERY BAND: Baby Class, Middle Class, Top Class, Nursery
        if (preg_match('/\bbaby(\s+class)?\b/i', $cleanQuestion)) {
            return [
                'found' => true,
                'response' => "Baby Class falls under Nursery. The fee is UGX 474,000 for Day Scholars or UGX 894,000 for Boarding, per term.",
                'category' => 'fees_nursery',
                'active_topic' => 'fees',
                'active_class' => 'Baby Class',
                'suggestions' => ["How do I apply?", "Does it include lunch?", "Uniform Prices"]
            ];
        }
        if (preg_match('/\bmiddle(\s+class)?\b/i', $cleanQuestion)) {
            return [
                'found' => true,
                'response' => "Middle Class falls under Nursery. The fee is UGX 474,000 for Day Scholars or UGX 894,000 for Boarding, per term.",
                'category' => 'fees_nursery',
                'active_topic' => 'fees',
                'active_class' => 'Middle Class',
                'suggestions' => ["How do I apply?", "Does it include lunch?", "Uniform Prices"]
            ];
        }
        if (preg_match('/\btop(\s+class)?\b/i', $cleanQuestion)) {
            return [
                'found' => true,
                'response' => "Top Class falls under Nursery. The fee is UGX 474,000 for Day Scholars or UGX 894,000 for Boarding, per term.",
                'category' => 'fees_nursery',
                'active_topic' => 'fees',
                'active_class' => 'Top Class',
                'suggestions' => ["How do I apply?", "Does it include lunch?", "Uniform Prices"]
            ];
        }
        if (preg_match('/\b(nursery|kindergarten|pre[\s\-]?primary)\b/i', $cleanQuestion)) {
            return [
                'found' => true,
                'response' => "Nursery, from Baby Class to Top Class, is UGX 474,000 for Day Scholars or UGX 894,000 for Boarding, per term.",
                'category' => 'fees_nursery',
                'active_topic' => 'fees',
                'active_class' => 'Nursery',
                'suggestions' => ["How do I apply?", "Does it include lunch?", "Uniform Prices"]
            ];
        }
        
        // P6–P7 BAND: Primary 6, Primary 7, P6, P7
        if (preg_match('/\b(primary\s*6|p\.?\s*6)\b/i', $cleanQuestion)) {
            return [
                'found' => true,
                'response' => "Primary 6 falls under P6–P7. The fee is UGX 629,000 for Day Scholars or UGX 1,094,000 for Boarding, per term.",
                'category' => 'fees_p6_p7',
                'active_topic' => 'fees',
                'active_class' => 'Primary 6',
                'suggestions' => ["How do I apply?", "Boarding Programme", "Uniform Prices"]
            ];
        }
        if (preg_match('/\b(primary\s*7|p\.?\s*7|candidate)\b/i', $cleanQuestion)) {
            return [
                'found' => true,
                'response' => "Primary 7 falls under P6–P7. The fee is UGX 629,000 for Day Scholars or UGX 1,094,000 for Boarding, per term.",
                'category' => 'fees_p6_p7',
                'active_topic' => 'fees',
                'active_class' => 'Primary 7',
                'suggestions' => ["How do I apply?", "Boarding Programme", "Uniform Prices"]
            ];
        }
        if (preg_match('/\b(p\.?\s*6\s*[\-–to]+\s*p?\.?\s*7|primary\s*6\s*[\-–to]+\s*7)\b/i', $cleanQuestion)) {
            return [
                'found' => true,
                'response' => "Primary 6 and Primary 7 (P6–P7) fees are UGX 629,000 for Day Scholars or UGX 1,094,000 for Boarding, per term.",
                'category' => 'fees_p6_p7',
                'active_topic' => 'fees',
                'active_class' => 'P6–P7',
                'suggestions' => ["How do I apply?", "Boarding Programme", "Uniform Prices"]
            ];
        }
        
        // P1–P5 BAND: Primary 1 to Primary 5 / P1 to P5
        if (preg_match('/\b(primary\s*1|p\.?\s*1)\b/i', $cleanQuestion)) {
            return [
                'found' => true,
                'response' => "Primary 1 falls under P1–P5. The fee is UGX 579,000 for Day Scholars or UGX 1,019,000 for Boarding, per term.",
                'category' => 'fees_p1_p5',
                'active_topic' => 'fees',
                'active_class' => 'Primary 1',
                'suggestions' => ["How do I apply?", "Does it include lunch?", "Uniform Prices"]
            ];
        }
        if (preg_match('/\b(primary\s*2|p\.?\s*2)\b/i', $cleanQuestion)) {
            return [
                'found' => true,
                'response' => "Primary 2 falls under P1–P5. The fee is UGX 579,000 for Day Scholars or UGX 1,019,000 for Boarding, per term.",
                'category' => 'fees_p1_p5',
                'active_topic' => 'fees',
                'active_class' => 'Primary 2',
                'suggestions' => ["How do I apply?", "Does it include lunch?", "Uniform Prices"]
            ];
        }
        if (preg_match('/\b(primary\s*3|p\.?\s*3)\b/i', $cleanQuestion)) {
            return [
                'found' => true,
                'response' => "Primary 3 falls under P1–P5. The fee is UGX 579,000 for Day Scholars or UGX 1,019,000 for Boarding, per term.",
                'category' => 'fees_p1_p5',
                'active_topic' => 'fees',
                'active_class' => 'Primary 3',
                'suggestions' => ["How do I apply?", "Does it include lunch?", "Uniform Prices"]
            ];
        }
        if (preg_match('/\b(primary\s*4|p\.?\s*4)\b/i', $cleanQuestion)) {
            return [
                'found' => true,
                'response' => "Primary 4 falls under P1–P5. The fee is UGX 579,000 for Day Scholars or UGX 1,019,000 for Boarding, per term.",
                'category' => 'fees_p1_p5',
                'active_topic' => 'fees',
                'active_class' => 'Primary 4',
                'suggestions' => ["How do I apply?", "Does it include lunch?", "Uniform Prices"]
            ];
        }
        if (preg_match('/\b(primary\s*5|p\.?\s*5)\b/i', $cleanQuestion)) {
            return [
                'found' => true,
                'response' => "Primary 5 falls under P1–P5. The fee is UGX 579,000 for Day Scholars or UGX 1,019,000 for Boarding, per term.",
                'category' => 'fees_p1_p5',
                'active_topic' => 'fees',
                'active_class' => 'Primary 5',
                'suggestions' => ["How do I apply?", "Does it include lunch?", "Uniform Prices"]
            ];
        }
        if (preg_match('/\b(p\.?\s*1\s*[\-–to]+\s*p?\.?\s*5|primary\s*1\s*[\-–to]+\s*5)\b/i', $cleanQuestion)) {
            return [
                'found' => true,
                'response' => "Primary 1 to Primary 5 (P1–P5) fees are UGX 579,000 for Day Scholars or UGX 1,019,000 for Boarding, per term.",
                'category' => 'fees_p1_p5',
                'active_topic' => 'fees',
                'active_class' => 'P1–P5',
                'suggestions' => ["How do I apply?", "Does it include lunch?", "Uniform Prices"]
            ];
        }
        
        return null;
    }
    
    /**
     * Checks if query is a generic fee inquiry.
     */
    private function isGenericFeeInquiry($cleanQuestion) {
        if (preg_match('/^(how\s+much\s+(are\s+the|is\s+the)?\s*school\s+fees|what\s+are\s+(the\s+school|your|the)?\s*fees|how\s+much\s+are\s+(the\s+)?fees|school\s+fees\??|fees\??|fee\s+structure\??|tell\s+me\s+about\s+(school\s+)?fees|what\s+is\s+the\s+fee\s+structure|what\s+do\s+i\s+pay\s+per\s+term|what\s+is\s+the\s+termly\s+fee|how\s+expensive\s+is\s+the\s+school|cost\s+per\s+term|how\s+much\s+does\s+school\s+cost)\b/i', trim($cleanQuestion))) {
            return true;
        }
        if (preg_match('/\bschool\s+fees\b/i', $cleanQuestion) && !preg_match('/\b(day|boarding|nursery|primary|p[1-7]|baby|middle|top)\b/i', $cleanQuestion)) {
            return true;
        }
        return false;
    }
    
    /**
     * Resolves class mentions like "Tell me about Primary 6" or "My child is joining Primary 6".
     */
    private function resolveClassMention($cleanQuestion) {
        if (preg_match('/\b(primary\s*6|p\.?\s*6)\b/i', $cleanQuestion)) {
            return [
                'found' => true,
                'response' => "Primary 6 is part of our upper primary section (P6–P7), focusing on rigorous academic mastery, leadership skills, and preparing learners for their candidate year.\n\nWould you like to know about Primary 6 fees or admissions?",
                'category' => 'class_p6',
                'active_topic' => 'primary',
                'active_class' => 'Primary 6',
                'suggestions' => ["How much does it cost?", "How do I apply?", "Do you offer boarding?"]
            ];
        }
        if (preg_match('/\b(primary\s*7|p\.?\s*7)\b/i', $cleanQuestion)) {
            return [
                'found' => true,
                'response' => "Primary 7 is our candidate class. Pupils undergo dedicated PLE coaching, revision packages, and mentorship leading to consistent First Grade results.\n\nWould you like to know about Primary 7 fees or admissions?",
                'category' => 'class_p7',
                'active_topic' => 'primary',
                'active_class' => 'Primary 7',
                'suggestions' => ["How much does it cost?", "How do I apply?", "Do you offer boarding?"]
            ];
        }
        if (preg_match('/\b(baby\s+class)\b/i', $cleanQuestion)) {
            return [
                'found' => true,
                'response' => "Baby Class welcomes 3-year-olds into a warm, joyful environment focusing on play-based learning, social skills, and sensory development.\n\nWould you like to know about Baby Class fees or admissions?",
                'category' => 'class_baby',
                'active_topic' => 'nursery',
                'active_class' => 'Baby Class',
                'suggestions' => ["How much does it cost?", "How do I apply?", "Does it include lunch?"]
            ];
        }
        if (preg_match('/\b(middle\s+class)\b/i', $cleanQuestion)) {
            return [
                'found' => true,
                'response' => "Middle Class (age 4) builds early literacy, phonics sounds, handwriting foundations, and pre-math concepts in a nurturing setting.\n\nWould you like to know about Middle Class fees or admissions?",
                'category' => 'class_middle',
                'active_topic' => 'nursery',
                'active_class' => 'Middle Class',
                'suggestions' => ["How much does it cost?", "How do I apply?", "Does it include lunch?"]
            ];
        }
        if (preg_match('/\b(top\s+class)\b/i', $cleanQuestion)) {
            return [
                'found' => true,
                'response' => "Top Class (age 5) focuses on reading fluency, basic arithmetic, and a confident transition into Primary 1.\n\nWould you like to know about Top Class fees or admissions?",
                'category' => 'class_top',
                'active_topic' => 'nursery',
                'active_class' => 'Top Class',
                'suggestions' => ["How much does it cost?", "How do I apply?", "Does it include lunch?"]
            ];
        }
        if (preg_match('/\b(primary\s*[1-5]|p\.?\s*[1-5])\b/i', $cleanQuestion)) {
            preg_match('/\b(primary\s*[1-5]|p\.?\s*[1-5])\b/i', $cleanQuestion, $m);
            $className = strtoupper(trim($m[0]));
            return [
                'found' => true,
                'response' => "$className is part of our lower & mid primary section (P1–P5), offering strong foundations in English, Mathematics, Science, and Social Studies.\n\nWould you like to know about $className fees or admissions?",
                'category' => 'class_p1_p5',
                'active_topic' => 'primary',
                'active_class' => $className,
                'suggestions' => ["How much does it cost?", "How do I apply?", "Do you offer boarding?"]
            ];
        }
        return null;
    }
    
    /**
     * Resolves multi-turn contextual follow-up questions (anaphora, ordinals, pronouns).
     */
    private function resolveContextualFollowUp($cleanQuestion, $normalized, $context) {
        $activeTopic = $context['active_topic'] ?? '';
        $lastCategory = $context['last_category'] ?? '';
        $activeClass = $context['active_class'] ?? null;
        
        // -------------------------------------------------------------
        // A. ORDINAL RESOLUTION: "the first one", "the second one", etc.
        // -------------------------------------------------------------
        // 1st = Nursery
        if (preg_match('/\b(first\s+one|first\s+programme|first\s+program|the\s+first\s+one|about\s+the\s+first\s+one|what\s+about\s+the\s+first)\b/i', $cleanQuestion)) {
            return [
                'found' => true,
                'response' => $this->knowledge['program_nursery']['response'],
                'category' => 'program_nursery',
                'active_topic' => 'nursery',
                'suggestions' => $this->knowledge['program_nursery']['suggestions']
            ];
        }
        // 2nd = Primary
        if (preg_match('/\b(second\s+one|second\s+programme|second\s+program|the\s+second\s+one|about\s+the\s+second\s+one|what\s+about\s+the\s+second)\b/i', $cleanQuestion)) {
            return [
                'found' => true,
                'response' => $this->knowledge['program_primary']['response'],
                'category' => 'program_primary',
                'active_topic' => 'primary',
                'suggestions' => $this->knowledge['program_primary']['suggestions']
            ];
        }
        // 3rd = Day School
        if (preg_match('/\b(third\s+one|third\s+programme|third\s+program|the\s+third\s+one|about\s+the\s+third\s+one|what\s+about\s+the\s+third)\b/i', $cleanQuestion)) {
            return [
                'found' => true,
                'response' => $this->knowledge['program_day']['response'],
                'category' => 'program_day',
                'active_topic' => 'day_school',
                'suggestions' => $this->knowledge['program_day']['suggestions']
            ];
        }
        // 4th / Last = Boarding
        if (preg_match('/\b(fourth\s+one|last\s+one|last\s+programme|last\s+program|the\s+last\s+one|about\s+the\s+last\s+one|tell\s+me\s+about\s+the\s+last\s+one|explain\s+the\s+last\s+one)\b/i', $cleanQuestion)) {
            return [
                'found' => true,
                'response' => $this->knowledge['program_boarding']['response'],
                'category' => 'program_boarding',
                'active_topic' => 'boarding',
                'suggestions' => $this->knowledge['program_boarding']['suggestions']
            ];
        }
        // "the other option" / "the other programme"
        if (preg_match('/\b(the\s+other\s+programme|the\s+other\s+program|the\s+other\s+option|other\s+option)\b/i', $cleanQuestion)) {
            if ($activeTopic === 'boarding' || $lastCategory === 'program_boarding') {
                return [
                    'found' => true,
                    'response' => $this->knowledge['program_day']['response'],
                    'category' => 'program_day',
                    'active_topic' => 'day_school',
                    'suggestions' => $this->knowledge['program_day']['suggestions']
                ];
            } else {
                return [
                    'found' => true,
                    'response' => $this->knowledge['program_boarding']['response'],
                    'category' => 'program_boarding',
                    'active_topic' => 'boarding',
                    'suggestions' => $this->knowledge['program_boarding']['suggestions']
                ];
            }
        }
        
        // -------------------------------------------------------------
        // B. MEAL / LUNCH INQUIRIES: "Does it include lunch?", "Is food provided?"
        // -------------------------------------------------------------
        if (preg_match('/\b(does\s+it\s+include\s+lunch|is\s+lunch\s+included|is\s+food\s+included|is\s+food\s+provided|do\s+they\s+get\s+food|what\s+do\s+they\s+eat)\b/i', $cleanQuestion)) {
            return [
                'found' => true,
                'response' => $this->knowledge['meals']['response'],
                'category' => 'meals',
                'active_topic' => 'meals',
                'suggestions' => $this->knowledge['meals']['suggestions']
            ];
        }
        
        // -------------------------------------------------------------
        // C. REQUIREMENTS INQUIRIES: "What do I need?", "What are the requirements?"
        // -------------------------------------------------------------
        if (preg_match('/^(what\s+do\s+i\s+need|what\s+are\s+the\s+requirements|what\s+is\s+required|requirements\??)\b/i', trim($cleanQuestion))) {
            if ($activeTopic === 'admission' || $lastCategory === 'admission') {
                return [
                    'found' => true,
                    'response' => $this->knowledge['admission_requirements']['response'],
                    'category' => 'admission_requirements',
                    'active_topic' => 'admission',
                    'suggestions' => $this->knowledge['admission_requirements']['suggestions']
                ];
            }
            if ($activeTopic === 'boarding' || $lastCategory === 'program_boarding') {
                return [
                    'found' => true,
                    'response' => "🏠 **Boarding Requirements:**\n\nFor boarding pupils, parents provide personal bedding (mattress, bedsheets, blanket), basin/bucket, personal hygiene items, and casual evening wear. All academic materials, meals, and medical care in the sick bay are provided by the school.\n\nWould you like to know the boarding fees or the admission steps?",
                    'category' => 'boarding_requirements',
                    'active_topic' => 'boarding',
                    'suggestions' => [
                        "Boarding Fees",
                        "How do I apply?",
                        "Contact Office"
                    ]
                ];
            }
            // General requirements
            return [
                'found' => true,
                'response' => $this->knowledge['admission_requirements']['response'],
                'category' => 'admission_requirements',
                'active_topic' => 'admission',
                'suggestions' => $this->knowledge['admission_requirements']['suggestions']
            ];
        }
        
        // -------------------------------------------------------------
        // D. CONTEXTUAL COST / FEE FOLLOW-UPS: "how much does it cost?", "what do I pay?", "how much is it?"
        // -------------------------------------------------------------
        $isFollowUpCost = preg_match('/^(how\s+much(\s+(is\s+it|does\s+it\s+cost|is\s+that|do\s+i\s+pay))?\??|what\s+do\s+i\s+pay\??|what\s+are\s+(its\s+)?fees\??|how\s+much\??)$/i', trim($cleanQuestion))
            || preg_match('/\b(how\s+much\s+(does\s+it\s+cost|is\s+it)|what\s+do\s+i\s+pay)\b/i', $cleanQuestion);
            
        if ($isFollowUpCost) {
            // Case 1: Specific class established in previous context (e.g. Primary 6)
            if ($activeClass) {
                if (preg_match('/\b(primary\s*6|p\.?\s*6|primary\s*7|p\.?\s*7)\b/i', $activeClass)) {
                    return [
                        'found' => true,
                        'response' => "$activeClass falls under P6–P7. Boarding is UGX 1,094,000 per term, while Day Scholar is UGX 629,000 per term.",
                        'category' => 'fees_p6_p7',
                        'active_topic' => 'fees',
                        'active_class' => $activeClass,
                        'suggestions' => ["How do I apply?", "Uniform Prices", "Contact Bursar"]
                    ];
                }
                if (preg_match('/\b(primary\s*[1-5]|p\.?\s*[1-5])\b/i', $activeClass)) {
                    return [
                        'found' => true,
                        'response' => "$activeClass falls under P1–P5. The fee is UGX 579,000 for Day Scholars or UGX 1,019,000 for Boarding, per term.",
                        'category' => 'fees_p1_p5',
                        'active_topic' => 'fees',
                        'active_class' => $activeClass,
                        'suggestions' => ["How do I apply?", "Does it include lunch?", "Uniform Prices"]
                    ];
                }
                if (preg_match('/\b(baby|middle|top|nursery)\b/i', $activeClass)) {
                    return [
                        'found' => true,
                        'response' => "$activeClass falls under Nursery. The fee is UGX 474,000 for Day Scholars or UGX 894,000 for Boarding, per term.",
                        'category' => 'fees_nursery',
                        'active_topic' => 'fees',
                        'active_class' => $activeClass,
                        'suggestions' => ["How do I apply?", "Does it include lunch?", "Uniform Prices"]
                    ];
                }
            }
            
            // Case 2: Established topic context
            if ($activeTopic === 'boarding' || $lastCategory === 'program_boarding') {
                return [
                    'found' => true,
                    'response' => "💰 **Official Boarding Fees (Per Term):**\n\n• **Nursery (Baby–Top Class):** UGX 894,000\n• **P1–P5:** UGX 1,019,000\n• **P6–P7:** UGX 1,094,000\n\nAll boarding fees are per term and include full accommodation, 5 nutritious meals daily, 24/7 matron & security supervision, sick bay medical care, and evening study prep. Would you like me to help you with a specific class?",
                    'category' => 'fees_boarding',
                    'active_topic' => 'boarding_fees',
                    'suggestions' => $this->knowledge['fees_boarding']['suggestions']
                ];
            }
            if ($activeTopic === 'day_school' || $lastCategory === 'program_day') {
                return [
                    'found' => true,
                    'response' => "💰 **Official Day Scholar Fees (Per Term):**\n\n• **Nursery (Baby–Top Class):** UGX 474,000\n• **P1–P5:** UGX 579,000\n• **P6–P7:** UGX 629,000\n\nAll day scholar fees are per term and include tuition, mid-morning snack with tea/porridge, hot lunch, and learning materials. Would you like me to help you with a specific class?",
                    'category' => 'fees_day',
                    'active_topic' => 'day_fees',
                    'suggestions' => $this->knowledge['fees_day']['suggestions']
                ];
            }
            if ($activeTopic === 'nursery' || $lastCategory === 'program_nursery') {
                return [
                    'found' => true,
                    'response' => "Nursery, from Baby Class to Top Class, is UGX 474,000 for Day Scholars or UGX 894,000 for Boarding, per term.",
                    'category' => 'fees_nursery',
                    'active_topic' => 'fees',
                    'suggestions' => ["How do I apply?", "Does it include lunch?", "Uniform Prices"]
                ];
            }
            if ($activeTopic === 'primary' || $lastCategory === 'program_primary') {
                return [
                    'found' => true,
                    'response' => "💰 **Primary School Fees (Per Term):**\n\n• **P1–P5:** UGX 579,000 Day Scholar / UGX 1,019,000 Boarding\n• **P6–P7:** UGX 629,000 Day Scholar / UGX 1,094,000 Boarding\n\nAll fees are per term. Would you like to know about a specific primary class?",
                    'category' => 'fees_p1_p5',
                    'active_topic' => 'fees',
                    'suggestions' => [
                        "How much is Primary 1?",
                        "How much is Primary 6?",
                        "How do I apply?"
                    ]
                ];
            }
            if ($activeTopic === 'uniforms' || $lastCategory === 'uniforms') {
                return [
                    'found' => true,
                    'response' => $this->knowledge['uniforms']['response'],
                    'category' => 'uniforms',
                    'active_topic' => 'uniforms',
                    'suggestions' => $this->knowledge['uniforms']['suggestions']
                ];
            }
            if ($activeTopic === 'transport' || $lastCategory === 'transport') {
                return [
                    'found' => true,
                    'response' => "Transport fees vary according to your residential pickup location in Kampala and Rubaga. Please contact our transport manager at **+256 701 420 506** for exact route pricing.",
                    'category' => 'transport_fees',
                    'active_topic' => 'transport',
                    'suggestions' => [
                        "Day School Programme",
                        "Contact Office"
                    ]
                ];
            }
        }
        
        // -------------------------------------------------------------
        // E. LOCATION & DIRECTIONS: "how do I get there?", "how do I reach?"
        // -------------------------------------------------------------
        if (preg_match('/\b(how\s+do\s+i\s+(get|reach)\s+there|how\s+can\s+i\s+(get|reach)\s+there|directions|where\s+is\s+that|how\s+do\s+i\s+reach|how\s+to\s+get\s+there|get\s+there|how\s+do\s+i\s+get\s+to\s+the\s+school|can\s+you\s+give\s+me\s+directions|give\s+me\s+directions|send\s+me\s+the\s+location|send\s+me\s+the\s+school\s+location)\b/i', $cleanQuestion)) {
            return [
                'found' => true,
                'response' => $this->knowledge['location_detailed']['response'],
                'category' => 'location_detailed',
                'active_topic' => 'location',
                'suggestions' => $this->knowledge['location_detailed']['suggestions']
            ];
        }
        
        // -------------------------------------------------------------
        // F. AGE INQUIRY: "what age is that for?"
        // -------------------------------------------------------------
        if (preg_match('/\b(what\s+age|which\s+age|how\s+old)\b/i', $cleanQuestion)) {
            if ($activeTopic === 'nursery' || $lastCategory === 'program_nursery') {
                return [
                    'found' => true,
                    'response' => "👶 **Ages for Nursery Section:**\n\n• **Baby Class:** 3 years old\n• **Middle Class:** 4 years old\n• **Top Class:** 5 years old\n\nChildren transition into Primary 1 at age 6. Would you like to check Nursery fees or admission requirements?",
                    'category' => 'nursery_age',
                    'active_topic' => 'nursery',
                    'suggestions' => [
                        "How much is Nursery?",
                        "How do I apply?",
                        "Does it include lunch?"
                    ]
                ];
            }
            if ($activeTopic === 'boarding' || $lastCategory === 'program_boarding') {
                return [
                    'found' => true,
                    'response' => "🏠 **Boarding Age Recommendations:**\n\nWe accept boarding pupils from age 4 and above (Nursery Top Class and Primary 1 through P.7). Boarding is especially popular and effective for Primary pupils to maximize supervised evening study.\n\nWould you like to know boarding fees or how to apply?",
                    'category' => 'boarding_age',
                    'active_topic' => 'boarding',
                    'suggestions' => [
                        "Boarding Fees",
                        "How do I apply?",
                        "Visit the School"
                    ]
                ];
            }
        }
        
        // -------------------------------------------------------------
        // G. "WHAT HAPPENS NEXT?": Admission progression
        // -------------------------------------------------------------
        if (preg_match('/\b(what\s+happens\s+next|next\s+step|what\s+next)\b/i', $cleanQuestion)) {
            if ($activeTopic === 'admission' || $lastCategory === 'admission') {
                return [
                    'found' => true,
                    'response' => "📝 **Next Steps for Admission:**\n\n1. Bring your child with their birth certificate, report card, and photos to our campus at 2 Gabunga Road, Kampala, Uganda.\n2. Complete our diagnostic assessment with a teacher.\n3. Receive the official admission letter and complete fee registration with the bursar!\n\nWould you like to speak directly with the admissions officer at **+256 772 420 506 / +256 701 420 506**?",
                    'category' => 'admission_next_steps',
                    'active_topic' => 'admission',
                    'suggestions' => [
                        "How much are the school fees?",
                        "Where is the school?",
                        "Contact Office"
                    ]
                ];
            }
        }
        
        // -------------------------------------------------------------
        // H. ADMISSION APPLICATION: "how do I apply?", "how to enroll?"
        // -------------------------------------------------------------
        if (preg_match('/\b(how\s+(do\s+i|to)\s+(apply|join|enroll|register))\b/i', $cleanQuestion)) {
            return [
                'found' => true,
                'response' => $this->knowledge['admission']['response'],
                'category' => 'admission',
                'active_topic' => 'admission',
                'suggestions' => $this->knowledge['admission']['suggestions']
            ];
        }
        
        return null;
    }
    
    /**
     * Domain entity boost based on key terminology and synonyms.
     */
    private function calculateEntityBoost($category, $question) {
        $boost = 0;
        
        // Programmes
        if ($category === 'programs' && (preg_match('/\b(program|programmes|programs|classes|levels|offer|curriculum)\b/i', $question))) {
            $boost += 35;
        }
        // Boarding
        if ($category === 'program_boarding' && (preg_match('/\b(boarding|boarder|stay\s+at\s+school|live\s+at\s+school|dormitory|hostel)\b/i', $question) && !preg_match('/\b(fees?|cost|price|tuition|pay)\b/i', $question))) {
            $boost += 45;
        }
        // Day school
        if ($category === 'program_day' && (preg_match('/\b(day\s*(school|scholar|pupil)|commute)\b/i', $question) && !preg_match('/\b(fees?|cost|price|tuition|pay)\b/i', $question))) {
            $boost += 45;
        }
        // Boarding fees
        if ($category === 'fees_boarding' && (preg_match('/\bboarding\b/i', $question) && preg_match('/\b(fees?|cost|price|tuition|pay|much)\b/i', $question))) {
            $boost += 60;
        }
        // Day fees
        if ($category === 'fees_day' && (preg_match('/\bday\b/i', $question) && preg_match('/\b(fees?|cost|price|tuition|pay|much)\b/i', $question))) {
            $boost += 60;
        }
        // Complete fees
        if ($category === 'fees_complete' && (preg_match('/\b(fees|fee\s+structure|tuition)\b/i', $question) && !preg_match('/\b(boarding|day|uniform)\b/i', $question))) {
            $boost += 50;
        }
        // Location & Directions
        if (($category === 'location' || $category === 'location_detailed') && (preg_match('/\b(where|location|address|situated|find\s+you|gabunga|plus\s+code|directions?|google\s+maps?|map|how\s+do\s+i\s+get|reach|get\s+there)\b/i', $question))) {
            $boost += 45;
        }
        // Admissions
        if ($category === 'admission' && (preg_match('/\b(admission|admissions|apply|enroll|join|register)\b/i', $question))) {
            $boost += 40;
        }
        // Director
        if ($category === 'director' && (preg_match('/\b(director|kimera|emmanuel|headteacher|principal|leader|who\s+is\s+in\s+charge)\b/i', $question))) {
            $boost += 50;
        }
        // Meals / Lunch
        if ($category === 'meals' && (preg_match('/\b(lunch|food|meal|meals|diet|feeding|eat)\b/i', $question))) {
            $boost += 50;
        }
        
        return $boost;
    }
    
    public function getQuickActions() {
        return [
            "What programmes do you offer?",
            "How much are the school fees?",
            "Do you offer boarding?",
            "How do I apply for admission?",
            "Where is the school located?",
            "What are your school hours?"
        ];
    }
}
