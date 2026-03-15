<?php
session_start();
include("includes/db.php");

$category_id = isset($_GET['category_id']) 
    ? (int)$_GET['category_id'] 
    : 1;

$questions_array = [];
$categories = [];
$cat_query = $conn->query("SELECT * FROM categories ORDER BY id ASC");

while($cat = $cat_query->fetch_assoc()){
    $categories[] = $cat;
}

$q = $conn->query("
    SELECT * FROM questions 
    WHERE category_id = $category_id
    ORDER BY id ASC
");

while($question = $q->fetch_assoc()){

    $choices_result = $conn->query("
        SELECT * FROM choices 
        WHERE question_id = {$question['id']}
        ORDER BY id ASC
    ");

    $choices = [];
    $letters = ['A','B','C','D'];
    $i = 0;

    $correct_letter = null;

    while($choice = $choices_result->fetch_assoc()){

        if($choice['is_correct'] == 1){
            $correct_letter = $letters[$i];
        }

        $choices[] = [
            "letter" => $letters[$i],
            "value" => $choice['choice_text'],
            "correct" => $choice['is_correct'] == 1
        ];

        $i++;
    }

    $questions_array[] = [
        "id" => $question['id'],
        "number" => count($questions_array) + 1,
        "text" => $question['question_text'],
        "choices" => $choices,
        "correctAnswer" => $correct_letter
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MATH QUIZ - Manage Quiz</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            background: #f4f4f4;
        }

        /* Hide autofill background and suggestions */
        input:-webkit-autofill,
        input:-webkit-autofill:hover,
        input:-webkit-autofill:focus,
        input:-webkit-autofill:active {
            -webkit-box-shadow: 0 0 0 30px #fff3cd inset !important;
            box-shadow: 0 0 0 30px #fff3cd inset !important;
            -webkit-text-fill-color: #333 !important;
            caret-color: #333;
            transition: background-color 5000s ease-in-out 0s;
        }

        /* Hide autofill icons and buttons */
        input::-webkit-contacts-auto-fill-button,
        input::-webkit-credentials-auto-fill-button,
        input::-webkit-autofill-button,
        input::-webkit-autofill-preview,
        input::-webkit-autofill-selected {
            visibility: hidden;
            display: none !important;
            pointer-events: none;
            height: 0;
            width: 0;
            margin: 0;
            opacity: 0;
        }

        /* For Firefox */
        input {
            filter: none;
        }

        /* Prevent browser from remembering form data */
        form {
            autocomplete: off;
        }

        /* Top Navigation Bar */
        .top-nav {
            background: #1a492b;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .logo-section h1 {
            color: white;
            font-size: 24px;
        }

        .hamburger {
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 5px;
            transition: background 0.3s;
        }

        .hamburger:hover {
            background: rgba(255,255,255,0.2);
        }

        .logout-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 1px solid rgba(255,255,255,0.3);
            padding: 10px 20px;
            border-radius: 5px;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            font-size: 16px;
            transition: background 0.3s;
            text-decoration: none;
            border: none;
        }

        .logout-btn:hover {
            background: rgba(255,255,255,0.3);
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 70px;
            left: -300px;
            width: 280px;
            height: calc(100vh - 70px);
            background: white;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            transition: left 0.3s ease;
            overflow-y: auto;
            z-index: 999;
        }

        .sidebar.open {
            left: 0;
        }

        .sidebar-header {
            padding: 20px;
            background: #1a492b;
            color: white;
        }

        .sidebar-menu {
            list-style: none;
            padding: 15px 0;
        }

        .menu-item {
            border-bottom: 1px solid #f0f0f0;
        }

        .menu-link {
            display: block;
            padding: 15px 20px;
            color: #333;
            text-decoration: none;
            transition: all 0.3s;
        }

        .menu-link:hover {
            background: #f8f9fa;
            color: #1a492b;
        }

        .overlay {
            position: fixed;
            top: 70px;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            display: none;
            z-index: 998;
        }

        .overlay.show {
            display: block;
        }

        /* Main Content */
        .main-content {
            margin-top: 80px;
            padding: 30px;
            max-width: 1400px;
            margin-left: auto;
            margin-right: auto;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .page-header h2 {
            color: #333;
            font-size: 28px;
            border-bottom: 3px solid #1a492b;
            padding-bottom: 10px;
        }

        .quiz-selector {
            display: flex;
            gap: 15px;
        }

        .quiz-tab {
            background: white;
            color: #333;
            border: 1px solid #ddd;
            padding: 10px 25px;
            border-radius: 30px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            transition: all 0.2s;
            text-decoration: none;
        }

        .quiz-tab:hover {
            background: #1a492b;
            color: white;
            border-color: #1a492b;
        }

        .quiz-tab.active {
            background: #1a492b;
            color: white;
            border-color: #1a492b;
        }

        .add-question-btn {
            background: #1a492b;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background 0.3s;
        }

        .add-question-btn:hover {
            background: #2e7d45;
        }

        /* Quiz Info Bar */
        .quiz-info-bar {
            background: white;
            padding: 15px 25px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .quiz-title {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .quiz-title h3 {
            color: #1a492b;
            font-size: 20px;
        }

        .quiz-stats {
            display: flex;
            gap: 20px;
            color: #666;
            font-size: 14px;
        }

        .quiz-stats span {
            font-weight: bold;
            color: #1a492b;
            margin-left: 5px;
        }

        /* Questions Table */
        .table-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow-x: auto;
        }

        .questions-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1000px;
        }

        .questions-table thead {
            background: #f8f9fa;
        }

        .questions-table th {
            color: #555;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 15px 12px;
            text-align: left;
            border-bottom: 2px solid #e0e0e0;
        }

        .questions-table td {
            padding: 15px 12px;
            border-bottom: 1px solid #f0f0f0;
            color: #333;
            font-size: 14px;
            vertical-align: middle;
        }

        .questions-table tbody tr:hover {
            background: #f8f9fa;
        }

        .questions-table tbody tr.editing {
            background: #fff3cd;
        }

        /* Question number column */
        .q-number {
            font-weight: bold;
            color: #1a492b;
            width: 60px;
            text-align: center;
        }

        /* Question text */
        .question-text {
            font-weight: 500;
            min-width: 200px;
        }

        /* Choices container */
        .choices-container {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .choice-row {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
        }

        .choice-letter {
            font-weight: bold;
            color: #1a492b;
            width: 25px;
        }

        .choice-value {
            color: #555;
        }

        .choice-value.correct {
            color: #00c53e;
            font-weight: bold;
        }

        .choice-value.correct::after {
            content: " ✓";
            color: #00c53e;
        }

        /* Correct answer badge */
        .correct-badge {
            display: inline-block;
            background: #d4edda;
            color: #155724;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
            margin-left: 5px;
        }

        /* Action buttons */
        .action-btn {
            background: none;
            border: none;
            font-size: 16px;
            cursor: pointer;
            margin: 0 3px;
            padding: 5px;
            border-radius: 3px;
            transition: all 0.2s;
        }

        .action-btn:hover {
            background: #f0f0f0;
        }

        .action-btn.edit:hover {
            color: #ffc107;
        }

        .action-btn.delete:hover {
            color: #dc3545;
        }

        .action-btn.save {
            color: #28a745;
        }

        .action-btn.cancel {
            color: #dc3545;
        }

        /* Edit mode inputs */
        .edit-input {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 14px;
        }

        .edit-input:focus {
            outline: none;
            border-color: #1a492b;
            box-shadow: 0 0 0 2px rgba(26,73,43,0.2);
        }

        .choices-edit {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }

        .choice-edit-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .choice-edit-item .choice-letter {
            font-size: 14px;
        }

        .choice-edit-item input {
            flex: 1;
            padding: 6px 8px;
            border: 1px solid #ced4da;
            border-radius: 4px;
        }

        .correct-select {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 10px;
        }

        .correct-select select {
            padding: 6px 8px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            background: white;
        }

        /* Scroll to top button */
        .scroll-top-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: #1a492b;
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            transition: all 0.3s;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
        }

        .scroll-top-btn.show {
            opacity: 1;
            visibility: visible;
        }

        .scroll-top-btn:hover {
            background: #2e7d45;
            transform: translateY(-3px);
        }

        .page-btn {
            padding: 8px 12px;
            border: 1px solid #ddd;
            background: white;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.2s;
        }

        .page-btn:hover {
            background: #1a492b;
            color: white;
            border-color: #1a492b;
        }

        .page-btn.active {
            background: #1a492b;
            color: white;
            border-color: #1a492b;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .quiz-selector {
                flex-wrap: wrap;
            }
            
            .quiz-info-bar {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <!-- Top Navigation Bar -->
    <div class="top-nav">
        <div class="logo-section">
            <button class="hamburger" onclick="toggleSidebar()">☰</button>
            <h1>MATH QUIZ</h1>
        </div>
        
        <button class="logout-btn" onclick="return confirm('Are you sure you want to log out?') ? window.location.href='index.php' : false;">
            <span>🚪</span>
            <span>LOG OUT</span>
        </button>
    </div>

    <!-- Overlay -->
    <div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h3>Menu</h3>
        </div>
        <ul class="sidebar-menu">
            <li class="menu-item"><a href="teacher_page.php" class="menu-link">📊 Dashboard</a></li>
            <li class="menu-item"><a href="check_scores.php" class="menu-link">📊 Check Scores</a></li>
            <li class="menu-item"><a href="manage_quiz.php" class="menu-link">📝 Manage Quiz</a></li>
            <li class="menu-item"><a href="manage_students.php" class="menu-link">👥 Manage Students</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="page-header">
            <h2>📝 Manage Quiz</h2>
            <div class="quiz-selector">
                <?php foreach($categories as $cat): ?>
                    <a href="?category_id=<?php echo $cat['id']; ?>"
                        class="quiz-tab <?php echo ($cat['id'] == $category_id) ? 'active' : ''; ?>">
                        <?php echo htmlspecialchars($cat['category_name']); ?>
                    </a>
            <?php endforeach; ?>
            </div>
        </div>

        <!-- Quiz Info Bar -->
        <div class="quiz-info-bar">
            <div class="quiz-title">
                <?php
                $current_category_name = '';
                foreach($categories as $cat){
                    if($cat['id'] == $category_id){
                        $current_category_name = $cat['category_name'];
                break;
                    }
                }
                ?>

                <h3><?php echo htmlspecialchars($current_category_name); ?> Quiz</h3>
            </div>
            <button class="add-question-btn" onclick="addNewQuestion()">
                <span>➕</span> Add Question
            </button>
        </div>

        <!-- Questions Table -->
        <div class="table-container">
            <table class="questions-table" id="questionsTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Question</th>
                        <th>Choices</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="questionsTableBody">
                    <!-- Table rows will be generated by JavaScript -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- Scroll to Top Button -->
    <button class="scroll-top-btn" id="scrollTopBtn" onclick="scrollToTop()">↑</button>

    <script>
        window.onerror = function(msg, url, lineNo, columnNo, error) {
            console.error('Error: ' + msg + '\nLine: ' + lineNo);
            alert('JavaScript Error: ' + msg + '\nLine: ' + lineNo);
            return false;
        }

        const questions = <?php echo json_encode($questions_array); ?>;
        console.log("Questions loaded:", questions);
        
        let editingId = null;

        // Helper function to create input fields with autofill prevention
        function createInputWithNoSuggestions(id, value) {
            const input = document.createElement('input');
            input.type = 'text';
            input.className = 'edit-input';
            input.id = id;
            input.value = value || '';
            
            // Disable all suggestions and autofill
            input.setAttribute('autocomplete', 'off');
            input.setAttribute('autocorrect', 'off');
            input.setAttribute('autocapitalize', 'off');
            input.setAttribute('spellcheck', 'false');
            input.setAttribute('aria-autocomplete', 'none');
            input.setAttribute('data-form-type', 'other');
            
            // Add a random name attribute to confuse browsers
            input.name = 'field_' + Math.random().toString(36).substr(2, 9);
            
            // Temporarily make readonly to prevent autofill, then remove on focus
            input.setAttribute('readonly', 'on');
            input.addEventListener('focus', function() {
                this.removeAttribute('readonly');
            });
            
            // Additional attributes for maximum compatibility
            input.setAttribute('autofill', 'off');
            input.setAttribute('aria-autocomplete', 'list');
            
            return input;
        }

        function displayQuestions() {
            console.log("Displaying questions. Editing ID:", editingId);
            
            const tbody = document.getElementById('questionsTableBody');
            if (!tbody) {
                console.error("Could not find questionsTableBody element");
                return;
            }
            
            tbody.innerHTML = '';

            questions.forEach((q, index) => {
                const row = document.createElement('tr');
                row.id = `question-${q.id}`;
                
                if (editingId == q.id) {
                    console.log("Rendering edit mode for question:", q.id);
                    row.className = 'editing';
                    
                    // Create edit mode row
                    const numberTd = document.createElement('td');
                    numberTd.className = 'q-number';
                    numberTd.textContent = q.number;
                    
                    const questionTd = document.createElement('td');
                    const questionInput = createInputWithNoSuggestions(`edit-question-${q.id}`, q.text);
                    questionTd.appendChild(questionInput);
                    
                    const choicesTd = document.createElement('td');
                    
                    // Choices edit container
                    const choicesEditDiv = document.createElement('div');
                    choicesEditDiv.className = 'choices-edit';
                    
                    q.choices.forEach(c => {
                        const choiceItem = document.createElement('div');
                        choiceItem.className = 'choice-edit-item';
                        
                        const letterSpan = document.createElement('span');
                        letterSpan.className = 'choice-letter';
                        letterSpan.textContent = c.letter + '.';
                        
                        const choiceInput = createInputWithNoSuggestions(`edit-choice-${q.id}-${c.letter}`, c.value);
                        
                        choiceItem.appendChild(letterSpan);
                        choiceItem.appendChild(choiceInput);
                        choicesEditDiv.appendChild(choiceItem);
                    });
                    
                    // Correct answer selector
                    const correctSelectDiv = document.createElement('div');
                    correctSelectDiv.className = 'correct-select';
                    
                    const correctLabel = document.createElement('label');
                    correctLabel.textContent = 'Correct:';
                    
                    const correctSelect = document.createElement('select');
                    correctSelect.id = `edit-correct-${q.id}`;
                    
                    const options = ['A', 'B', 'C', 'D'];
                    options.forEach(letter => {
                        const option = document.createElement('option');
                        option.value = letter;
                        option.textContent = letter;
                        if (q.correctAnswer === letter) {
                            option.selected = true;
                        }
                        correctSelect.appendChild(option);
                    });
                    
                    correctSelectDiv.appendChild(correctLabel);
                    correctSelectDiv.appendChild(correctSelect);
                    
                    choicesTd.appendChild(choicesEditDiv);
                    choicesTd.appendChild(correctSelectDiv);
                    
                    const actionsTd = document.createElement('td');
                    
                    const saveBtn = document.createElement('button');
                    saveBtn.className = 'action-btn save';
                    saveBtn.innerHTML = '💾';
                    saveBtn.onclick = function() { saveQuestion(q.id); };
                    
                    const cancelBtn = document.createElement('button');
                    cancelBtn.className = 'action-btn cancel';
                    cancelBtn.innerHTML = '✖';
                    cancelBtn.onclick = cancelEdit;
                    
                    actionsTd.appendChild(saveBtn);
                    actionsTd.appendChild(cancelBtn);
                    
                    row.appendChild(numberTd);
                    row.appendChild(questionTd);
                    row.appendChild(choicesTd);
                    row.appendChild(actionsTd);
                } else {
                    // Display mode
                    const numberTd = document.createElement('td');
                    numberTd.className = 'q-number';
                    numberTd.textContent = q.number;
                    
                    const questionTd = document.createElement('td');
                    questionTd.className = 'question-text';
                    questionTd.textContent = q.text;
                    
                    const choicesTd = document.createElement('td');
                    const choicesContainer = document.createElement('div');
                    choicesContainer.className = 'choices-container';
                    
                    q.choices.forEach(c => {
                        const choiceRow = document.createElement('div');
                        choiceRow.className = 'choice-row';
                        
                        const letterSpan = document.createElement('span');
                        letterSpan.className = 'choice-letter';
                        letterSpan.textContent = c.letter + '.';
                        
                        const valueSpan = document.createElement('span');
                        valueSpan.className = 'choice-value' + (c.correct ? ' correct' : '');
                        valueSpan.textContent = c.value;
                        
                        choiceRow.appendChild(letterSpan);
                        choiceRow.appendChild(valueSpan);
                        choicesContainer.appendChild(choiceRow);
                    });
                    
                    choicesTd.appendChild(choicesContainer);
                    
                    const actionsTd = document.createElement('td');
                    
                    const editBtn = document.createElement('button');
                    editBtn.className = 'action-btn edit';
                    editBtn.innerHTML = '✏️';
                    editBtn.onclick = function() { 
                        console.log("Edit button clicked for ID:", q.id);
                        editQuestion(q.id); 
                    };
                    
                    const deleteBtn = document.createElement('button');
                    deleteBtn.className = 'action-btn delete';
                    deleteBtn.innerHTML = '🗑️';
                    deleteBtn.onclick = function() { 
                        console.log("Delete button clicked for ID:", q.id);
                        deleteQuestion(q.id); 
                    };
                    
                    actionsTd.appendChild(editBtn);
                    actionsTd.appendChild(deleteBtn);
                    
                    row.appendChild(numberTd);
                    row.appendChild(questionTd);
                    row.appendChild(choicesTd);
                    row.appendChild(actionsTd);
                }
                
                tbody.appendChild(row);
            });
        }

        function editQuestion(id) {
            console.log("editQuestion called with id:", id, "type:", typeof id);
            editingId = id;
            console.log("editingId set to:", editingId);
            displayQuestions();
            
            // Scroll to the editing row
            setTimeout(() => {
                const row = document.getElementById(`question-${id}`);
                if (row) {
                    row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }, 100);
        }

        // Save question
        function saveQuestion(id) {
            console.log("========== SAVE QUESTION CALLED ==========");
            console.log("Question ID being saved:", id);
        
            const questionText = document.getElementById(`edit-question-${id}`).value;
            const correctLetter = document.getElementById(`edit-correct-${id}`).value;
        
            console.log("Question text:", questionText);
            console.log("Correct letter:", correctLetter);

            let choices = [];
            ["A","B","C","D"].forEach(letter => {
                const choiceValue = document.getElementById(`edit-choice-${id}-${letter}`).value;
                choices.push(choiceValue);
                console.log(`Choice ${letter}:`, choiceValue);
            });

            // Validate inputs
            if (!questionText.trim()) {
                alert("Question text cannot be empty");
                return;
            }
        
            for (let i = 0; i < choices.length; i++) {
                if (!choices[i].trim()) {
                    alert(`Choice ${String.fromCharCode(65 + i)} cannot be empty`);
                    return;
                }
            }

            let url, bodyData;
        
            if(id === "new"){
                url = "add_question.php";
                bodyData = "category_id=<?php echo $category_id; ?>" +
                    "&question_text=" + encodeURIComponent(questionText) +
                    "&correct_letter=" + correctLetter +
                    "&choices[]=" + encodeURIComponent(choices[0]) +
                    "&choices[]=" + encodeURIComponent(choices[1]) +
                    "&choices[]=" + encodeURIComponent(choices[2]) +
                    "&choices[]=" + encodeURIComponent(choices[3]);
                console.log("ADDING NEW QUESTION");
            } else {
                url = "update_question.php";
                bodyData = "question_id=" + id +
                    "&question_text=" + encodeURIComponent(questionText) +
                    "&correct_letter=" + correctLetter +
                    "&choices[]=" + encodeURIComponent(choices[0]) +
                    "&choices[]=" + encodeURIComponent(choices[1]) +
                    "&choices[]=" + encodeURIComponent(choices[2]) +
                    "&choices[]=" + encodeURIComponent(choices[3]);
                console.log("UPDATING EXISTING QUESTION ID:", id);
            }
        
            console.log("URL:", url);
            console.log("Body data:", bodyData);

            // Show loading state
            const saveBtn = document.querySelector(`#question-${id} .save`);
            if (saveBtn) {
                saveBtn.disabled = true;
                saveBtn.innerHTML = '⏳';
            }

            fetch(url, {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: bodyData
            })
            .then(async response => {
                console.log("Response status:", response.status);
                
                const text = await response.text();
                console.log("RAW RESPONSE TEXT:", text);
                
                try {
                    const data = JSON.parse(text);
                    console.log("PARSED JSON DATA:", data);
                    return data;
                } catch (e) {
                    console.error("FAILED TO PARSE JSON:", e);
                    console.error("RAW RESPONSE (first 500 chars):", text.substring(0, 500));
                    throw new Error("Server returned invalid JSON. Check console for details.");
                }
            })
            .then(data => {
                console.log("SUCCESS DATA:", data);
                if(data.success){
                    // Scroll to top before reloading
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                    setTimeout(() => {
                        location.reload();
                    }, 500);
                } else {
                    alert("Error: " + (data.message || "Unknown error"));
                    if (saveBtn) {
                        saveBtn.disabled = false;
                        saveBtn.innerHTML = '💾';
                    }
                }
            })
            .catch(error => {
                alert("Error: " + error.message);
                console.error("FETCH ERROR:", error);
                if (saveBtn) {
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = '💾';
                }
            });
        }

        // Cancel edit
        function cancelEdit() {
            editingId = null;
            displayQuestions();
        }

        // Delete question
        function deleteQuestion(id) {
            if (confirm("Are you sure you want to delete this question?")) {
                fetch("delete_question.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded"
                    },
                    body: "question_id=" + id
                })
                .then(res => res.json())
                .then(data => {
                    if(data.success){
                        alert("Question deleted!");
                        location.reload();
                    } else {
                        alert("Error deleting question");
                    }
                })
                .catch(error => {
                    alert("Fetch error: " + error);
                });
            }
        }

        // Add new question
        function addNewQuestion() {
            // Prevent multiple edit mode
            if (editingId !== null) {
                alert("Finish editing first.");
                return;
            }

            const tempId = "new";
            editingId = tempId;

            const newQuestion = {
                id: tempId,
                number: questions.length + 1,
                text: "",
                choices: [
                    { letter: "A", value: "", correct: false },
                    { letter: "B", value: "", correct: false },
                    { letter: "C", value: "", correct: false },
                    { letter: "D", value: "", correct: false }
                ],
                correctAnswer: "A"
            };

            questions.push(newQuestion);
            displayQuestions();

            // Scroll to the new question form
            setTimeout(() => {
                const newRow = document.getElementById(`question-${tempId}`);
                if (newRow) {
                    newRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }, 100);
        }

        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('overlay');

            sidebar.classList.toggle('open');
            overlay.classList.toggle('show');
        }

        // Close sidebar when clicking outside
        document.addEventListener('click', function(event) {
            var sidebar = document.getElementById('sidebar');
            var hamburger = document.querySelector('.hamburger');
            
            if (sidebar && hamburger) {
                if (!sidebar.contains(event.target) && !hamburger.contains(event.target)) {
                    sidebar.classList.remove('open');
                    document.getElementById('overlay').classList.remove('show');
                }
            }
        });

        // Scroll to top button functionality
        const scrollTopBtn = document.getElementById('scrollTopBtn');

        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 300) {
                scrollTopBtn.classList.add('show');
            } else {
                scrollTopBtn.classList.remove('show');
            }
        });

        function scrollToTop() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }

        // Initial display
        displayQuestions();

        window.testEdit = function() {
            editQuestion(1);
            console.log("Test edit called");
        };
    </script>
</body>
</html>