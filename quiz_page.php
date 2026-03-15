<?php
session_start();
include("includes/db.php");

// Check if user is logged in and is a student
if(!isset($_SESSION["user_id"]) || $_SESSION["role"] != "student"){
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 1;

// Get category info
$category_query = $conn->prepare("SELECT * FROM categories WHERE id = ?");
$category_query->bind_param("i", $category_id);
$category_query->execute();
$category_result = $category_query->get_result();
$category = $category_result->fetch_assoc();

if(!$category) {
    header("Location: student_page.php");
    exit();
}

// Get student's previous attempts for this category
$attempt_query = $conn->prepare("
    SELECT * FROM quiz_attempts 
    WHERE user_id = ? AND category_id = ? 
    ORDER BY attempt_number DESC
");
$attempt_query->bind_param("ii", $user_id, $category_id);
$attempt_query->execute();
$attempt_result = $attempt_query->get_result();

$attempts = [];
$failed_attempts = 0;
$passed = false;

while($attempt = $attempt_result->fetch_assoc()) {
    $attempts[] = $attempt;
    if($attempt['score'] >= 7) { // Pass if score 7 or above (70%)
        $passed = true;
    } else {
        $failed_attempts++;
    }
}

$total_attempts = count($attempts);
$next_attempt_number = $total_attempts + 1;

// Check if can retake
$can_retake = true;
$retry_message = '';

if($passed) {
    $can_retake = false;
    $retry_message = "You have already passed this quiz. You cannot retake it.";
} elseif($failed_attempts >= 2) {
    $can_retake = false;
    $retry_message = "You have failed this quiz twice. You cannot retake it again.";
}

// Get random 10 questions for this category (only if can retake)
$questions = [];
$total_questions = 0;

if($can_retake) {
    $questions_query = $conn->prepare("
        SELECT q.* 
        FROM questions q 
        WHERE q.category_id = ? 
        ORDER BY RAND()
        LIMIT 10
    ");
    $questions_query->bind_param("i", $category_id);
    $questions_query->execute();
    $questions_result = $questions_query->get_result();

    while($q = $questions_result->fetch_assoc()) {
        // Get choices for each question
        $choices_query = $conn->prepare("
            SELECT * FROM choices 
            WHERE question_id = ? 
            ORDER BY RAND()
        ");
        $choices_query->bind_param("i", $q['id']);
        $choices_query->execute();
        $choices_result = $choices_query->get_result();
        
        $choices = [];
        while($c = $choices_result->fetch_assoc()) {
            $choices[] = $c;
        }
        
        $q['choices'] = $choices;
        $questions[] = $q;
    }
    
    $total_questions = count($questions);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MATH QUIZ - <?php echo htmlspecialchars($category['category_name']); ?> Quiz</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }

        body {
            background: #f4f4f4;
        }

        /* Top Navigation Bar */
        .top-nav {
            background: #1a492b;
            padding: 12px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .logo-section h1 {
            color: white;
            font-size: 22px;
        }

        .back-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 1px solid rgba(255,255,255,0.3);
            padding: 8px 15px;
            border-radius: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s;
            text-decoration: none;
        }

        .back-btn:hover {
            background: rgba(255,255,255,0.3);
        }

        /* Main Content */
        .main-content {
            margin-top: 70px;
            padding: 20px;
            max-width: 1000px;
            margin-left: auto;
            margin-right: auto;
        }

        /* Quiz Container - Slightly Bigger */
        .quiz-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        /* Quiz Header - Slightly Bigger */
        .quiz-header {
            background: linear-gradient(135deg, #1a492b 0%, #2e7d45 100%);
            color: white;
            padding: 20px 25px;
            text-align: center;
        }

        .quiz-header h2 {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .quiz-header p {
            font-size: 16px;
            opacity: 0.9;
        }

        .attempt-badge {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            padding: 4px 12px;
            border-radius: 20px;
            margin-top: 8px;
            font-size: 13px;
        }

        /* Score Bar */
        .score-bar {
            display: flex;
            justify-content: space-between;
            padding: 12px 25px;
            background: #f8f9fa;
            border-bottom: 1px solid #e0e0e0;
        }

        .score-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 16px;
        }

        .score-label {
            font-weight: bold;
            color: #666;
            font-size: 14px;
        }

        .score-value {
            font-size: 22px;
            font-weight: bold;
        }

        .correct .score-value {
            color: #00c53e;
        }

        .wrong .score-value {
            color: #dc3545;
        }

        /* Quiz Info Bar */
        .quiz-info-bar {
            background: white;
            padding: 12px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e0e0e0;
            font-size: 15px;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info-label {
            color: #666;
            font-weight: 500;
        }

        .info-value {
            font-weight: bold;
            color: #1a492b;
            font-size: 18px;
        }

        .info-value.warning {
            color: #ff9800;
        }

        .info-value.danger {
            color: #dc3545;
        }

        /* Question Container */
        .question-container {
            padding: 25px;
        }

        .question-number {
            color: #1a492b;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .question-text {
            font-size: 20px;
            font-weight: bold;
            color: #333;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f0f0f0;
        }

        /* Options Grid */
        .options-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 25px;
        }

        .option-item {
            background: #f8f9fa;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .option-item:hover {
            border-color: #1a492b;
            background: #f0f7f2;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(26,73,43,0.1);
        }

        .option-item.selected {
            border-color: #1a492b;
            background: #e8f5e9;
        }

        .option-letter {
            background: #1a492b;
            color: white;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-weight: bold;
            font-size: 16px;
        }

        .option-text {
            font-size: 16px;
            color: #333;
            font-weight: 500;
        }

        /* Loading Overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.8);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 2000;
        }

        .loading-overlay.show {
            display: flex;
        }

        .loading-spinner {
            width: 45px;
            height: 45px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid #1a492b;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Result Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 2000;
            justify-content: center;
            align-items: center;
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 15px;
            padding: 30px;
            width: 90%;
            max-width: 450px;
            text-align: center;
            animation: modalSlideIn 0.3s ease;
        }

        @keyframes modalSlideIn {
            from {
                transform: translateY(-30px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-content h2 {
            color: #1a492b;
            font-size: 24px;
            margin-bottom: 20px;
        }

        .result-score {
            font-size: 42px;
            font-weight: bold;
            color: #1a492b;
            margin: 20px 0;
        }

        .result-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 25px 0;
        }

        .result-correct {
            background: #d4edda;
            padding: 20px;
            border-radius: 10px;
        }

        .result-wrong {
            background: #f8d7da;
            padding: 20px;
            border-radius: 10px;
        }

        .result-label {
            font-size: 13px;
            color: #666;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .result-number {
            font-size: 32px;
            font-weight: bold;
        }

        .result-percentage {
            font-size: 22px;
            color: #1a492b;
            margin: 20px 0;
            font-weight: bold;
        }

        .result-message {
            font-size: 16px;
            color: #dc3545;
            margin: 10px 0;
            font-weight: bold;
        }

        .modal-buttons {
            display: flex;
            gap: 12px;
            margin-top: 25px;
        }

        .modal-btn {
            flex: 1;
            padding: 14px;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .modal-btn.primary {
            background: #1a492b;
            color: white;
        }

        .modal-btn.primary:hover {
            background: #2e7d45;
        }

        .modal-btn.secondary {
            background: #6c757d;
            color: white;
        }

        .modal-btn.secondary:hover {
            background: #5a6268;
        }

        .modal-btn.danger {
            background: #dc3545;
            color: white;
        }

        .modal-btn.danger:hover {
            background: #c82333;
        }

        /* Access Denied */
        .access-denied {
            background: white;
            border-radius: 12px;
            padding: 40px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .access-denied h2 {
            color: #dc3545;
            font-size: 24px;
            margin-bottom: 15px;
        }

        .access-denied p {
            color: #666;
            font-size: 18px;
            margin-bottom: 25px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .options-grid {
                grid-template-columns: 1fr;
            }
            
            .score-bar {
                flex-direction: column;
                gap: 10px;
            }
            
            .quiz-info-bar {
                flex-wrap: wrap;
                gap: 15px;
            }
            
            .main-content {
                padding: 15px;
            }
            
            .modal-content {
                padding: 25px;
            }
            
            .result-details {
                grid-template-columns: 1fr;
            }
            
            .modal-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <!-- Top Navigation Bar -->
    <div class="top-nav">
        <div class="logo-section">
            <a href="#" class="back-btn" id="backToQuizzesBtn">← Back to Quizzes</a>
            <h1>MATH QUIZ</h1>
        </div>
        <!-- Logout button removed -->
    </div>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <?php if(!$can_retake): ?>
            <!-- Access Denied -->
            <div class="access-denied">
                <h2>⛔ Quiz Not Available</h2>
                <p><?php echo htmlspecialchars($retry_message); ?></p>
                <a href="quizzes.php" class="modal-btn secondary" style="display: inline-block; width: auto; padding: 12px 30px;">Back to Quizzes</a>
            </div>
        <?php elseif($total_questions == 0): ?>
            <!-- No Questions -->
            <div class="access-denied">
                <h2>😕 No Questions Available</h2>
                <p>This quiz doesn't have any questions yet. Please check back later.</p>
                <a href="quizzes.php" class="modal-btn secondary" style="display: inline-block; width: auto; padding: 12px 30px;">Back to Quizzes</a>
            </div>
        <?php else: ?>
            <!-- Quiz Container -->
            <div class="quiz-container">
                <!-- Quiz Header -->
                <div class="quiz-header">
                    <h2><?php echo htmlspecialchars($category['category_name']); ?> QUIZ</h2>
                    <p>Test your <?php echo strtolower(htmlspecialchars($category['category_name'])); ?> skills</p>
                    <div class="attempt-badge">
                        Attempt #<?php echo $next_attempt_number; ?> (Max 2 attempts)
                    </div>
                </div>

                <!-- Score Bar (Correct/Wrong) -->
                <div class="score-bar">
                    <div class="score-item correct">
                        <span class="score-label">Correct:</span>
                        <span class="score-value" id="correctCount">0</span>
                    </div>
                    <div class="score-item wrong">
                        <span class="score-label">Wrong:</span>
                        <span class="score-value" id="wrongCount">0</span>
                    </div>
                </div>

                <!-- Quiz Info Bar (Timer, Accuracy, Progress) -->
                <div class="quiz-info-bar">
                    <div class="info-item">
                        <span class="info-label">⏱️ Timer:</span>
                        <span class="info-value" id="timer">10s</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">📊 Accuracy:</span>
                        <span class="info-value" id="accuracy">0%</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">📌 Progress:</span>
                        <span class="info-value" id="progress">0/10</span>
                    </div>
                </div>

                <!-- Question Container -->
                <div class="question-container" id="questionContainer">
                    <!-- Questions will be loaded here via JavaScript -->
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Result Modal -->
    <div class="modal" id="resultModal">
        <div class="modal-content">
            <h2 id="resultTitle">Quiz Complete! 🎉</h2>
            <div class="result-score" id="finalScore">0/10</div>
            
            <div class="result-details">
                <div class="result-correct">
                    <div class="result-label">Correct</div>
                    <div class="result-number" id="finalCorrect">0</div>
                </div>
                <div class="result-wrong">
                    <div class="result-label">Wrong</div>
                    <div class="result-number" id="finalWrong">0</div>
                </div>
            </div>

            <div class="result-percentage" id="finalPercentage">0%</div>
            <div class="result-message" id="resultMessage"></div>

            <div class="modal-buttons">
                <a href="quizzes.php" class="modal-btn secondary" id="backToQuizzesResultBtn">Back to Quizzes</a>
                <a href="quiz_page.php?category_id=<?php echo $category_id; ?>" class="modal-btn primary" id="tryAgainBtn">Try Again</a>
            </div>
        </div>
    </div>

    <script>
        // PHP data to JavaScript
        const questions = <?php echo json_encode($questions); ?>;
        const categoryId = <?php echo $category_id; ?>;
        const totalQuestions = <?php echo $total_questions; ?>;
        const nextAttemptNumber = <?php echo $next_attempt_number; ?>;
        
        // Quiz state
        let currentIndex = 0;
        let answers = [];
        let quizCompleted = false;
        let nextQuestionTimeout;
        let timerInterval;
        let timeLeft = 10; // 10 seconds per question

        // Initialize answers array
        questions.forEach(() => {
            answers.push({
                selected: null,
                isCorrect: false
            });
        });

        // Show/hide loading overlay
        function showLoading() {
            document.getElementById('loadingOverlay').classList.add('show');
        }

        function hideLoading() {
            document.getElementById('loadingOverlay').classList.remove('show');
        }

        // Start timer for current question
        function startTimer() {
            timeLeft = 10;
            updateTimerDisplay();
            
            timerInterval = setInterval(() => {
                if (!quizCompleted) {
                    timeLeft--;
                    updateTimerDisplay();
                    
                    if (timeLeft <= 0) {
                        // Time's up - count as wrong
                        clearInterval(timerInterval);
                        
                        // Mark as wrong if no answer selected
                        if (answers[currentIndex].selected === null) {
                            // Count as wrong
                            answers[currentIndex].selected = -1; // Special value for timeout
                            answers[currentIndex].isCorrect = false;
                            updateScores();
                            
                            // Move to next question or finish
                            if (currentIndex === questions.length - 1) {
                                finishQuiz();
                            } else {
                                currentIndex++;
                                displayQuestion();
                                startTimer();
                            }
                        }
                    }
                }
            }, 1000);
        }

        // Update timer display with color coding
        function updateTimerDisplay() {
            const timerElement = document.getElementById('timer');
            timerElement.textContent = timeLeft + 's';
            
            // Color code based on time left
            timerElement.classList.remove('warning', 'danger');
            if (timeLeft <= 3) {
                timerElement.classList.add('danger');
            } else if (timeLeft <= 5) {
                timerElement.classList.add('warning');
            }
        }

        // Display current question
        function displayQuestion() {
            if (questions.length === 0) return;
            
            const container = document.getElementById('questionContainer');
            const q = questions[currentIndex];
            
            if (!q) return;

            const selectedAnswer = answers[currentIndex].selected;
            
            let html = `
                <div class="question-number">Question ${currentIndex + 1}</div>
                <div class="question-text">${q.question_text}</div>
                <div class="options-grid" id="optionsGrid">
            `;

            const letters = ['A', 'B', 'C', 'D'];
            q.choices.forEach((choice, idx) => {
                const letter = letters[idx];
                let optionClass = 'option-item';
                
                if (selectedAnswer === idx) {
                    optionClass += ' selected';
                }
                
                html += `
                    <div class="${optionClass}" onclick="selectOption(${idx})">
                        <span class="option-letter">${letter}</span>
                        <span class="option-text">${choice.choice_text}</span>
                    </div>
                `;
            });

            html += `</div>`;
            
            container.innerHTML = html;

            // Update progress
            document.getElementById('progress').textContent = `${currentIndex + 1}/${questions.length}`;
        }

        // Select an option
        function selectOption(optionIndex) {
            if (quizCompleted) return;
            
            // Clear any pending timeout
            if (nextQuestionTimeout) {
                clearTimeout(nextQuestionTimeout);
            }
            
            // Clear timer
            clearInterval(timerInterval);
            
            // Check if already selected
            if (answers[currentIndex].selected === optionIndex) {
                // Deselect
                answers[currentIndex].selected = null;
                answers[currentIndex].isCorrect = false;
                
                // Update scores
                updateScores();
                
                // Refresh display
                displayQuestion();
                
                // Restart timer
                startTimer();
            } else {
                // Select new option
                answers[currentIndex].selected = optionIndex;
                
                // Check if correct
                const q = questions[currentIndex];
                answers[currentIndex].isCorrect = q.choices[optionIndex].is_correct == 1;
                
                // Update scores
                updateScores();
                
                // Refresh display
                displayQuestion();
                
                // If this is the last question, finish quiz
                if (currentIndex === questions.length - 1) {
                    // Small delay to show the selected state
                    nextQuestionTimeout = setTimeout(() => {
                        finishQuiz();
                    }, 300);
                } else {
                    // Move to next question after a short delay
                    nextQuestionTimeout = setTimeout(() => {
                        currentIndex++;
                        displayQuestion();
                        startTimer();
                    }, 300);
                }
            }
        }

        // Update score display
        function updateScores() {
            const correct = answers.filter(a => a.isCorrect).length;
            const wrong = answers.filter(a => a.selected !== null && a.selected !== -1 && !a.isCorrect).length;
            const timeout = answers.filter(a => a.selected === -1).length;
            const total = correct + wrong + timeout;
            
            document.getElementById('correctCount').textContent = correct;
            document.getElementById('wrongCount').textContent = wrong + timeout;
            
            // Update accuracy
            const accuracy = total > 0 ? Math.round((correct / total) * 100) : 0;
            document.getElementById('accuracy').textContent = accuracy + '%';
        }

        // Finish quiz
        function finishQuiz() {
            quizCompleted = true;
            clearInterval(timerInterval);
            
            const correct = answers.filter(a => a.isCorrect).length;
            const wrong = answers.filter(a => a.selected !== null && a.selected !== -1 && !a.isCorrect).length;
            const timeout = answers.filter(a => a.selected === -1).length;
            const total = questions.length;
            const percentage = total > 0 ? Math.round((correct / total) * 100) : 0;
            
            // Determine if passed or failed (70% or 7/10)
            const passed = correct >= 7;
            
            // Update modal based on result
            const title = passed ? '🎉 Quiz Complete! You Passed!' : '😕 Quiz Complete! You Failed!';
            document.getElementById('resultTitle').textContent = title;
            
            let message = '';
            if (passed) {
                message = 'Congratulations! You passed the quiz!';
            } else {
                if (nextAttemptNumber >= 2) {
                    message = 'You have used both attempts. You cannot retake this quiz again.';
                    document.getElementById('tryAgainBtn').style.display = 'none';
                } else {
                    message = 'You failed. You have 1 more attempt remaining.';
                }
            }
            document.getElementById('resultMessage').textContent = message;
            
            // Update modal
            document.getElementById('finalScore').textContent = `${correct}/${total}`;
            document.getElementById('finalCorrect').textContent = correct;
            document.getElementById('finalWrong').textContent = wrong + timeout;
            document.getElementById('finalPercentage').textContent = percentage + '%';
            
            // Show modal
            document.getElementById('resultModal').classList.add('show');
            
            // Save results to database
            saveResults(correct, wrong + timeout, total, passed);
        }

        // Save results to database
        function saveResults(correct, wrong, total, passed) {
            const score = correct;
            
            showLoading();
            
            fetch('save_quiz_attempt.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'category_id=' + categoryId + 
                      '&score=' + score + 
                      '&correct=' + correct + 
                      '&wrong=' + wrong + 
                      '&total=' + total + 
                      '&attempt_number=' + nextAttemptNumber +
                      '&passed=' + (passed ? 1 : 0)
            })
            .then(response => response.json())
            .then(data => {
                hideLoading();
                if (!data.success) {
                    console.error('Error saving results:', data.message);
                }
            })
            .catch(error => {
                hideLoading();
                console.error('Error:', error);
            });
        }

        // Save current progress (for back button)
        function saveCurrentProgress() {
            const correct = answers.filter(a => a.isCorrect).length;
            const wrong = answers.filter(a => a.selected !== null && a.selected !== -1 && !a.isCorrect).length;
            const timeout = answers.filter(a => a.selected === -1).length;
            const total = questions.length;
            const answered = answers.filter(a => a.selected !== null).length;
            
            // Only save if at least one question was answered
            if (answered > 0) {
                showLoading();
                
                return fetch('save_quiz_attempt.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'category_id=' + categoryId + 
                          '&score=' + correct + 
                          '&correct=' + correct + 
                          '&wrong=' + (wrong + timeout) + 
                          '&total=' + total + 
                          '&attempt_number=' + nextAttemptNumber +
                          '&passed=0'
                })
                .then(response => response.json())
                .then(data => {
                    hideLoading();
                    return true;
                })
                .catch(error => {
                    hideLoading();
                    console.error('Error:', error);
                    return false;
                });
            } else {
                // No answers, resolve immediately
                return Promise.resolve(true);
            }
        }

        // Back button confirmation
        document.getElementById('backToQuizzesBtn').addEventListener('click', function(e) {
            e.preventDefault();
            
            // Check if any question has been answered
            const answered = answers.filter(a => a.selected !== null).length;
            
            if (answered > 0 && !quizCompleted) {
                if (confirm('⚠️ Leave Quiz?\n\nYour progress will be saved and you will be redirected to the quizzes page.')) {
                    saveCurrentProgress().then(() => {
                        window.location.href = 'quizzes.php';
                    });
                }
            } else {
                window.location.href = 'quizzes.php';
            }
        });

        // Initialize quiz
        if (questions.length > 0) {
            displayQuestion();
            startTimer();
        }
    </script>
</body>
</html>