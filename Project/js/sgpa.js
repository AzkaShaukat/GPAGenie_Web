// SGPA Calculator - Pure JavaScript Version
document.addEventListener('DOMContentLoaded', function() {
    // DOM Elements
    const courseTableBody = document.getElementById('course-table-body');
    const addCourseBtn = document.querySelector('.add-course-btn');
    const sgpaForm = document.getElementById('sgpaCalcForm');
    const resultArea = document.getElementById('result-area');
    const sgpaValueSpan = document.getElementById('sgpa-value');
    const resultTableBody = document.getElementById('result-table-body');
    const resultTotalCredits = document.getElementById('result-total-credits');
    const resultTotalPoints = document.getElementById('result-total-points');
    const errorMessageDiv = document.getElementById('error-message');
    const printResultBtn = document.getElementById('printResultBtn');
    const progressRing = document.getElementById('progress-ring-foreground');
    const progressText = document.getElementById('progress-circle-text');


    // Initialize progress circle
    let radius = 70;
    let circumference = 2 * Math.PI * radius;
    if (progressRing) {
        progressRing.style.strokeDasharray = `${circumference} ${circumference}`;
        progressRing.style.strokeDashoffset = circumference;
    }



    // Add course row
    if (addCourseBtn && courseTableBody) {
        addCourseBtn.addEventListener('click', function() {
            const newRow = createCourseRow();
            courseTableBody.appendChild(newRow);
        });
    }

    // Remove course row (event delegation)
    if (courseTableBody) {
        courseTableBody.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-course-btn') || e.target.closest('.remove-course-btn')) {
                const row = e.target.closest('.course-row');
                if (row) row.remove();
            }
        });
    }

    // Print result button
    if (printResultBtn) {
        printResultBtn.addEventListener('click', printResultAreaContent);
    }

    // Form submission
    if (sgpaForm) {
        sgpaForm.addEventListener('submit', function(e) {
            e.preventDefault();
            calculateSGPA();
        });
    }

    // Helper function to create a new course row
    function createCourseRow() {
        const row = document.createElement('tr');
        row.classList.add('course-row');
        row.innerHTML = `
            <td><input type="text" class="form-control course-name" placeholder="Subject Name"></td>
            <td>
                <select class="form-control course-grade">
                    <option value="4.3">A+</option>
                    <option value="4.0">A</option>
                    <option value="3.7">A-</option>
                    <option value="3.3">B+</option>
                    <option value="3.0" selected>B</option>
                    <option value="2.7">B-</option>
                    <option value="2.3">C+</option>
                    <option value="2.0">C</option>
                    <option value="1.7">C-</option>
                    <option value="1.3">D+</option>
                    <option value="1.0">D</option>
                    <option value="0.7">D-</option>
                    <option value="0.0">F</option>
                    <option value="-1">Ignore (P/NP/I/W)</option>
                </select>
            </td>
            <td><input type="number" class="form-control course-credits" placeholder="e.g. 3" min="0.5" step="0.5" value="3"></td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-danger remove-course-btn" title="Remove Course">&times;</button>
            </td>
        `;
        return row;
    }

    // Main calculation function
    function calculateSGPA() {
        clearErrors();

        let totalPoints = 0;
        let totalCredits = 0;
        const courses = [];
        let hasValidInput = false;
        let errorMessages = [];
        let formIsValid = true;

        const courseRows = courseTableBody.querySelectorAll('.course-row');
        const previousStudentInfo = document.getElementById('result-student-info');
        if (previousStudentInfo) previousStudentInfo.remove();

        if (courseRows.length === 0) {
            errorMessages.push("Please add at least one course.");
            displayErrors(errorMessages);
            resultArea.style.display = 'none';
            sgpaValueSpan.textContent = '0.00';
            resultTableBody.innerHTML = '';
            resultTotalCredits.textContent = '';
            resultTotalPoints.textContent = '';
            updateProgressCircle(0);
            return;
        }

        courseRows.forEach((row, index) => {
            const courseNameInput = row.querySelector('.course-name');
            const gradeSelect = row.querySelector('.course-grade');
            const creditsInput = row.querySelector('.course-credits');

            const courseName = courseNameInput.value.trim() || `Course ${index + 1}`;
            const gradeValue = parseFloat(gradeSelect.value);
            const creditsValue = parseFloat(creditsInput.value);


            let rowHasError = false;
            if (gradeValue !== -1 && (isNaN(creditsValue) || creditsValue <= 0)) {
                errorMessages.push(`Invalid or missing credits for ${courseName}. Credits must be a positive number.`);
                creditsInput.closest('.form-group')?.classList.add('has-error');
                rowHasError = true;
                formIsValid = false;
            }

            if (isNaN(gradeValue)) {
                errorMessages.push(`Invalid grade selected for ${courseName}.`);
                gradeSelect.closest('.form-group')?.classList.add('has-error');
                rowHasError = true;
                formIsValid = false;
            }

            if (!rowHasError) {
                if (gradeValue === -1) {
                    courses.push({ 
                        name: courseName, 
                        grade: 'Ignored', 
                        credits: creditsValue || 0, 
                        points: 0 
                    });
                } else {
                    hasValidInput = true;
                    const points = gradeValue * creditsValue;
                    totalPoints += points;
                    totalCredits += creditsValue;
                    courses.push({
                        name: courseName,
                        grade: gradeSelect.options[gradeSelect.selectedIndex].text,
                        credits: creditsValue,
                        points: points.toFixed(2)
                    });
                }
            }
        });



        if (errorMessages.length > 0) {
            displayErrors(errorMessages);
            populateResultTable(courses);
            populateStudentInfoResult();
            resultTotalCredits.textContent = totalCredits > 0 ? totalCredits.toFixed(1) : '0.0';
            resultTotalPoints.textContent = totalPoints > 0 ? totalPoints.toFixed(2) : '0.00';

            if (totalCredits > 0) {
                const sgpa = totalPoints / totalCredits;
                sgpaValueSpan.textContent = sgpa.toFixed(2);
                updateProgressCircle(sgpa);
            } else {
                sgpaValueSpan.textContent = 'N/A';
                updateProgressCircle(0);
            }
            resultArea.style.display = 'block';
            scrollToElement(errorMessageDiv);
        }
        else if (hasValidInput && totalCredits > 0) {
            const sgpa = totalPoints / totalCredits;
            sgpaValueSpan.textContent = sgpa.toFixed(2);
            populateResultTable(courses);
            populateStudentInfoResult();
            resultTotalCredits.textContent = totalCredits.toFixed(1);
            resultTotalPoints.textContent = totalPoints.toFixed(2);
            updateProgressCircle(sgpa);
            resultArea.style.display = 'block';
            scrollToElement(resultArea);
        } else if (!hasValidInput && courseRows.length > 0 && errorMessages.length === 0) {
            displayErrors(["No courses eligible for SGPA calculation were entered."]);
            populateResultTable(courses);
            populateStudentInfoResult();
            resultTotalCredits.textContent = '0.0';
            resultTotalPoints.textContent = '0.00';
            sgpaValueSpan.textContent = 'N/A';
            updateProgressCircle(0);
            resultArea.style.display = 'block';
            scrollToElement(resultArea);
        } else {
            displayErrors(["Please add courses and enter valid credits and grades to calculate SGPA."]);
            resultArea.style.display = 'none';
            sgpaValueSpan.textContent = '0.00';
            resultTableBody.innerHTML = '';
            resultTotalCredits.textContent = '';
            resultTotalPoints.textContent = '';
            updateProgressCircle(0);
        }
    }

    function clearErrors() {
        errorMessageDiv.innerHTML = '';
        errorMessageDiv.style.display = 'none';
        document.querySelectorAll('.form-group.has-error').forEach(el => {
            el.classList.remove('has-error');
        });
    }

    function displayErrors(messages) {
        errorMessageDiv.innerHTML = messages.join('<br>');
        errorMessageDiv.style.display = 'block';
    }

    function populateResultTable(courseData) {
        resultTableBody.innerHTML = '';
        courseData.forEach(course => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${escapeHtml(course.name)}</td>
                <td>${escapeHtml(course.grade)}</td>
                <td>${course.credits.toFixed(1)}</td>
                <td>${course.grade === 'Ignored' ? '-' : course.points}</td>
            `;
            resultTableBody.appendChild(row);
        });
    }

    function populateStudentInfoResult() {
        const name = document.getElementById('name')?.value.trim() || 'N/A';
        const subject = document.getElementById('subject')?.value.trim() || 'N/A';
        const email = document.getElementById('email')?.value.trim() || 'N/A';
        const sendCard = document.getElementById('result-card')?.checked || false;

        let studentInfoContainer = document.getElementById('result-student-info');
        if (!studentInfoContainer) {
            studentInfoContainer = document.createElement('div');
            studentInfoContainer.id = 'result-student-info';
            studentInfoContainer.className = 'student-info-result mb-3';
            const resultContainer = resultArea.querySelector('.result-container');
            if (resultContainer) {
                resultContainer.insertBefore(studentInfoContainer, resultContainer.firstChild);
            }
        }

        studentInfoContainer.innerHTML = `
            <h3 class="h5">Student Details:</h3>
            <p><strong>Name:</strong> ${escapeHtml(name)}</p>
            <p><strong>Favorite Subject:</strong> ${escapeHtml(subject)}</p>
            <p><strong>Email:</strong> ${escapeHtml(email)}</p>
            <p><strong>Send Result Card:</strong> ${sendCard ? 'Yes' : 'No'}</p>
        `;
    }

    function printResultAreaContent() {
        const contentToPrint = resultArea.innerHTML;
        const printWindow = window.open('', '_blank');
        printWindow.document.open();
        printWindow.document.write(`
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>SGPA Result - GPA Genie</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
                <style>
                    body {
                        font-family: 'Segoe UI', sans-serif;
                        padding: 20px;
                        color: #333;
                    }
                    .result-container {
                        max-width: none !important;
                        margin: 0 auto;
                        padding: 0 !important;
                    }
                    #printResultBtn {
                        display: none !important;
                    }
                    .progress-circle-container {
                        display: none !important;
                    }
                    .section-title {
                        border-bottom: 2px solid #30d07b;
                        padding-bottom: 0.5rem;
                        margin-bottom: 1rem;
                        display: block;
                        text-align: center;
                        color: #333 !important;
                    }
                    .sgpa-display {
                        text-align: center;
                        margin-bottom: 20px;
                    }
                    .sgpa-score {
                        color: #27ae60 !important;
                    }
                    .student-info-result {
                        margin-bottom: 20px;
                        border: 1px solid #dee2e6;
                        padding: 15px;
                    }
                    .student-info-result h3 {
                        color: #333;
                        font-size: 1.2rem;
                        margin-bottom: 10px;
                    }
                    .student-info-result p {
                        margin-bottom: 5px;
                        font-size: 1.1rem;
                    }
                    .result-table {
                        width: 100%;
                        margin-bottom: 1rem;
                        color: #212529;
                        border-collapse: collapse;
                    }
                    .result-table th,
                    .result-table td {
                        padding: 8px;
                        vertical-align: top;
                        border: 1px solid #dee2e6;
                        text-align: left;
                        font-size: 1.1rem;
                    }
                    .result-table th {
                        font-weight: bold;
                        background-color: #e9ecef;
                    }
                    .result-table tbody tr:nth-of-type(odd) {
                        background-color: rgba(0, 0, 0, 0.05);
                    }
                    .result-table tfoot td {
                        font-weight: bold;
                        background-color: #dee2e6;
                    }
                    .result-table td:nth-child(3),
                    .result-table td:nth-child(4),
                    .result-table th:nth-child(3),
                    .result-table th:nth-child(4) {
                        text-align: center;
                    }
                </style>
            </head>
            <body>
                <div class="container">${contentToPrint}</div>
                <script>
                    setTimeout(function() {
                        window.print();
                    }, 500);
                </script>
            </body>
            </html>
        `);
        printWindow.document.close();
    }

    function updateProgressCircle(sgpa) {
        if (!progressRing || !progressText) return;

        const maxGpa = 4.3;
        const validSgpa = Math.max(0, Math.min(isNaN(sgpa) ? 0 : sgpa, maxGpa));
        const percentage = (validSgpa / maxGpa) * 100;
        const offset = circumference - (percentage / 100) * circumference;
        progressRing.style.strokeDashoffset = offset;
        progressText.textContent = `${percentage.toFixed(0)}%`;
    }

    function escapeHtml(unsafe) {
        if (typeof unsafe !== 'string') return unsafe;
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function scrollToElement(element) {
        if (!element) return;
        element.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    }
});