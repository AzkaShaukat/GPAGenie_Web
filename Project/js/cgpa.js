document.addEventListener('DOMContentLoaded', function() {

    const semesterInputsArea = document.getElementById('semester-inputs-area');
    const addSemesterBtn = document.getElementById('addSemesterBtn');
    const deleteSemesterBtn = document.getElementById('deleteSemesterBtn');
    const cgpaCalcForm = document.getElementById('cgpaCalcForm');
    const calculateCgpaBtn = document.getElementById('calculateCgpaBtn');
    const resultArea = document.getElementById('result-area');
    const cgpaValueSpan = document.getElementById('cgpa-value');
    const resultTableBody = document.getElementById('result-table-body');
    const resultTotalCredits = document.getElementById('result-total-credits');
    const resultTotalPoints = document.getElementById('result-total-points');
    const errorMessageDiv = document.getElementById('error-message');
    const printResultBtn = document.getElementById('printResultBtn');
    const studentInfoResultPlaceholder = document.getElementById('result-student-info-placeholder');

    const progressRing = document.getElementById('progress-ring-foreground');
    const progressText = document.getElementById('progress-circle-text');
    let radius = 70;
    let circumference = 2 * Math.PI * radius;
    if (progressRing) {
        radius = progressRing.r.baseVal.value;
        circumference = 2 * Math.PI * radius;
        // Initialize Progress Circle
        progressRing.style.strokeDasharray = `${circumference} ${circumference}`;
        progressRing.style.strokeDashoffset = circumference;
    }

    addSemesterBtn.addEventListener('click', function() {
        addSemesterInputRow();
        checkCalculateButtonVisibility();
        clearErrors();
        resultArea.style.display = 'none';
        const studentInfo = document.getElementById('result-student-info');
        if (studentInfo) studentInfo.remove();
    });

    // Remove Individual Semester Row (delegated event)
    semesterInputsArea.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-semester-row')) {
            const row = e.target.closest('.semester-input-row');
            row.style.transition = 'all 0.3s';
            row.style.height = row.offsetHeight + 'px';
            
            setTimeout(() => {
                row.style.height = '0';
                row.style.paddingTop = '0';
                row.style.paddingBottom = '0';
                row.style.marginBottom = '0';
                row.style.borderWidth = '0';
                row.style.overflow = 'hidden';
            }, 10);
            
            setTimeout(() => {
                row.remove();
                updateSemesterNumbers();
                checkCalculateButtonVisibility();
                clearErrors();
                resultArea.style.display = 'none';
                const studentInfo = document.getElementById('result-student-info');
                if (studentInfo) studentInfo.remove();
            }, 310);
        }
    });

    // Print Result Button
    printResultBtn.addEventListener('click', printResultAreaContent);

    // Calculate CGPA on Form Submit
    cgpaCalcForm.addEventListener('submit', function(e) {
        e.preventDefault();
        calculateCGPA();
    });

    // --- Functions ---
    // Check if Calculate Button Should Be Visible
    function checkCalculateButtonVisibility() {
        const semesterRows = semesterInputsArea.querySelectorAll('.semester-input-row');
        if (semesterRows.length === 0) {
            calculateCgpaBtn.style.display = 'none';
        } else {
            calculateCgpaBtn.style.display = 'block';
        }
    }
function addSemesterInputRow() {
    const numExistingSemesters = semesterInputsArea.querySelectorAll('.semester-input-row').length;
    const newSemesterNum = numExistingSemesters + 1;

    const semesterRow = document.createElement('div');
    semesterRow.className = 'semester-input-row';
    semesterRow.dataset.semester = newSemesterNum;
    semesterRow.style.display = 'none';
    
    semesterRow.innerHTML = `
        <label for="semester-${newSemesterNum}-sgpa" class="control-label">Semester ${newSemesterNum}:</label>
        <div class="form-group">
            <input type="number" id="semester-${newSemesterNum}-sgpa" class="form-control semester-sgpa" placeholder="SGPA" step="0.01" min="0" max="4.3" required>
        </div>
        <div class="form-group">
            <input type="number" id="semester-${newSemesterNum}-credits" class="form-control semester-credits" placeholder="Credits" step="0.5" min="0.5" required>
        </div>
        <button type="button" class="btn btn-xs btn-danger remove-semester-row" title="Remove Semester">&times;</button>
    `;
    
    semesterInputsArea.appendChild(semesterRow);
    
    setTimeout(() => {
        semesterRow.style.display = 'flex';
    }, 10);
}    // Update Semester Numbers
    function updateSemesterNumbers() {
        const semesterRows = semesterInputsArea.querySelectorAll('.semester-input-row');
        semesterRows.forEach((row, index) => {
            const semesterNum = index + 1;
            row.dataset.semester = semesterNum;
            const label = row.querySelector('label.control-label');
            label.textContent = `Semester ${semesterNum}:`;
            const sgpaInput = row.querySelector('.semester-sgpa');
            sgpaInput.id = `semester-${semesterNum}-sgpa`;
            const creditsInput = row.querySelector('.semester-credits');
            creditsInput.id = `semester-${semesterNum}-credits`;
        });
    }

    // Calculate CGPA
    function calculateCGPA() {
        clearErrors();

        let cumulativePoints = 0;
        let cumulativeCredits = 0;
        const semesterResults = [];
        let errorMessages = [];
        let formIsValid = true;
        let hasValidSemesterInput = false;

        const semesterRows = semesterInputsArea.querySelectorAll('.semester-input-row');
        const studentInfo = document.getElementById('result-student-info');
        if (studentInfo) studentInfo.remove();

        if (semesterRows.length === 0) {
            errorMessages.push("Please add at least one semester to calculate CGPA.");
            formIsValid = false;
        } else {
            semesterRows.forEach(row => {
                const sgpaInput = row.querySelector('.semester-sgpa');
                const creditsInput = row.querySelector('.semester-credits');
                const sgpaGroup = sgpaInput.closest('.form-group');
                const creditsGroup = creditsInput.closest('.form-group');
                const semesterNum = parseInt(row.dataset.semester);

                const sgpaValue = parseFloat(sgpaInput.value);
                const creditsValue = parseFloat(creditsInput.value);

                let rowHasError = false;

                if (isNaN(sgpaValue) || sgpaValue < 0 || sgpaValue > 4.3) {
                    errorMessages.push(`Semester ${semesterNum}: Invalid SGPA (must be 0-4.3).`);
                    sgpaGroup.classList.add('has-error');
                    rowHasError = true;
                    formIsValid = false;
                }

                if (isNaN(creditsValue) || creditsValue <= 0) {
                    errorMessages.push(`Semester ${semesterNum}: Invalid Credits (must be positive).`);
                    creditsGroup.classList.add('has-error');
                    rowHasError = true;
                    formIsValid = false;
                }

                const displaySgpa = isNaN(sgpaValue) ? 'Invalid' : sgpaValue.toFixed(2);
                const displayCredits = isNaN(creditsValue) ? 'Invalid' : creditsValue.toFixed(1);
                let displayPoints = 'N/A';

                if (!rowHasError) {
                    hasValidSemesterInput = true;
                    const semesterPoints = sgpaValue * creditsValue;
                    cumulativePoints += semesterPoints;
                    cumulativeCredits += creditsValue;
                    displayPoints = semesterPoints.toFixed(2);
                }

                semesterResults.push({
                    semester: semesterNum,
                    sgpa: displaySgpa,
                    credits: displayCredits,
                    points: displayPoints
                });
            });
        }

        const sendCardCheckbox = document.getElementById('result-card');
        if (sendCardCheckbox.checked) {
            const nameInput = document.getElementById('name');
            const emailInput = document.getElementById('email');

            if (nameInput.value.trim() === '') {
                errorMessages.push("Name is required to include details in the result card.");
                nameInput.closest('.form-group').classList.add('has-error');
                formIsValid = false;
            }

            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (emailInput.value.trim() !== '' && !emailPattern.test(emailInput.value.trim())) {
                errorMessages.push("Please enter a valid Email address if including details.");
                emailInput.closest('.form-group').classList.add('has-error');
                formIsValid = false;
            }
        }

        populateResultTable(semesterResults);
        populateStudentInfoResult();

        if (!formIsValid) {
            displayErrors(errorMessages);
            resultTotalCredits.textContent = cumulativeCredits > 0 ? cumulativeCredits.toFixed(1) : '0.0';
            resultTotalPoints.textContent = cumulativePoints > 0 ? cumulativePoints.toFixed(2) : '0.00';
            cgpaValueSpan.textContent = 'N/A';
            updateProgressCircle(0);
            resultArea.style.display = 'block';
            scrollToElement(errorMessageDiv);
        } else if (hasValidSemesterInput && cumulativeCredits > 0) {
            const cgpa = cumulativePoints / cumulativeCredits;
            cgpaValueSpan.textContent = cgpa.toFixed(2);
            resultTotalCredits.textContent = cumulativeCredits.toFixed(1);
            resultTotalPoints.textContent = cumulativePoints.toFixed(2);
            updateProgressCircle(cgpa);
            resultArea.style.display = 'block';
            scrollToElement(resultArea);
        } else {
            errorMessages.push("No valid data entered (e.g., all credits were 0). Cannot calculate CGPA.");
            displayErrors(errorMessages);
            resultTotalCredits.textContent = '0.0';
            resultTotalPoints.textContent = '0.00';
            cgpaValueSpan.textContent = 'N/A';
            updateProgressCircle(0);
            resultArea.style.display = 'block';
            scrollToElement(errorMessageDiv);
        }
    }

    // Clear Error Messages and Input Highlights
    function clearErrors() {
        errorMessageDiv.innerHTML = '';
        errorMessageDiv.style.display = 'none';
        
        const formGroups = document.querySelectorAll('#cgpaCalcForm .form-group, #studentInfoForm .form-group');
        formGroups.forEach(group => {
            group.classList.remove('has-error');
        });
    }

    // Display Error Messages
    function displayErrors(messages) {
        errorMessageDiv.innerHTML = messages.join('<br>');
        errorMessageDiv.style.display = 'block';
    }

    // Populate Student Info in Result Area
    function populateStudentInfoResult() {
        const name = document.getElementById('name').value.trim();
        const subject = document.getElementById('subject').value.trim();
        const email = document.getElementById('email').value.trim();
        const includeDetails = document.getElementById('result-card').checked;

        const studentInfo = document.getElementById('result-student-info');
        if (studentInfo) studentInfo.remove();

        if (includeDetails && name) {
            const studentInfoHtml = `
                <div id="result-student-info" class="student-info-result">
                    <h3>Student Details:</h3>
                    <p><strong>Name:</strong> ${escapeHtml(name)}</p>
                    <p><strong>Major/Program:</strong> ${escapeHtml(subject || 'N/A')}</p>
                    <p><strong>Email:</strong> ${escapeHtml(email || 'N/A')}</p>
                </div>
            `;
            const div = document.createElement('div');
            div.innerHTML = studentInfoHtml;
            studentInfoResultPlaceholder.parentNode.insertBefore(div.firstChild, studentInfoResultPlaceholder);
        }
    }

    // Populate the Result Table
    function populateResultTable(semesterData) {
        resultTableBody.innerHTML = '';
        if (!semesterData || semesterData.length === 0) return;

        semesterData.forEach(semester => {
            const rowHtml = `
                <tr>
                    <td>Semester ${semester.semester}</td>
                    <td>${escapeHtml(semester.sgpa)}</td>
                    <td>${escapeHtml(semester.credits)}</td>
                    <td>${escapeHtml(semester.points)}</td>
                </tr>
            `;
            const div = document.createElement('div');
            div.innerHTML = rowHtml;
            resultTableBody.appendChild(div.firstChild);
        });
    }

    // Update Custom SVG Progress Circle
    function updateProgressCircle(cgpa) {
        if (!progressRing) return;
        const maxGpa = 4.3;
        const validCgpa = Math.max(0, Math.min(isNaN(cgpa) ? 0 : cgpa, maxGpa));
        const percentage = (validCgpa / maxGpa) * 100;
        const offset = circumference - (percentage / 100) * circumference;
        progressRing.style.strokeDashoffset = offset;
        if (progressText) {
            progressText.textContent = `${percentage.toFixed(0)}%`;
        }
    }

    // Print Result Area Content
    function printResultAreaContent() {
        const contentToPrint = resultArea.innerHTML;
        const printWindow = window.open('', '_blank');
        printWindow.document.open();
        printWindow.document.write(`
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>CGPA Result - GPA Genie</title>
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
                        padding: 20px !important;
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
                    .cgpa-display {
                        text-align: center;
                        margin-bottom: 20px;
                    }
                    .cgpa-score {
                        color: #27ae60 !important;
                        font-size: 2.5rem;
                    }
                    .student-info-result {
                        margin-bottom: 20px;
                        border: 1px solid #dee2e6;
                        padding: 15px;
                    }
                    .result-table {
                        width: 100%;
                        margin-bottom: 1rem;
                        border-collapse: collapse;
                    }
                    .result-table th,
                    .result-table td {
                        padding: 8px;
                        border: 1px solid #dee2e6;
                        text-align: center;
                    }
                    .result-table th {
                        background-color: #f8f9fa;
                        font-weight: bold;
                    }
                    .result-table tfoot td {
                        font-weight: bold;
                        background-color: #e9ecef;
                    }
                </style>
            </head>
            <body>
                <div class="container">${contentToPrint}</div>
                <script>
                    window.onload = function() {
                        setTimeout(function() {
                            window.print();
                        }, 500);
                    }
                </script>
            </body>
            </html>
        `);
        printWindow.document.close();
    }

    // Basic HTML Escaping Function
    function escapeHtml(unsafe) {
        if (typeof unsafe !== 'string') return unsafe;
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    // Smooth Scroll to Element
    function scrollToElement(element) {
        if (!element) return;
        const elementPosition = element.getBoundingClientRect().top + window.pageYOffset;
        const offsetPosition = elementPosition - 50;

        window.scrollTo({
            top: offsetPosition,
            behavior: 'smooth'
        });
    }

    // Initialize the page with no semesters
    checkCalculateButtonVisibility();
    updateProgressCircle(0);
});