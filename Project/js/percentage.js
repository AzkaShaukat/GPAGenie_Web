document.addEventListener('DOMContentLoaded', function() {
    
    const scoredMarks = document.getElementById('scoredMarks');
    const totalMarks = document.getElementById('totalMarks');
    const percentageResult = document.getElementById('percentageResult');
    
    
    // Calculate percentage on input change
    scoredMarks.addEventListener('input', calculatePercentage);
    totalMarks.addEventListener('input', calculatePercentage);
    
    // Format validation for scored marks input
    scoredMarks.addEventListener('blur', function() {
        const value = parseFloat(this.value);
        if (!isNaN(value)) {
            // Ensure value is not negative
            if (value < 0) this.value = 0;
            
            // Ensure scored marks don't exceed total marks
            const totalValue = parseFloat(totalMarks.value);
            if (!isNaN(totalValue) && value > totalValue) {
                this.value = totalValue;
                showInputWarning(this, 'Scored marks cannot exceed total marks');
            }
        }
        calculatePercentage();
    });
    
    // Format validation for total marks input
    totalMarks.addEventListener('blur', function() {
        const value = parseFloat(this.value);
        if (isNaN(value) || value <= 0) {
            this.value = 100;
            showInputWarning(this, 'Total marks must be greater than zero');
        }
        calculatePercentage();
    });
    
    // --- Functions ---
    
    // Calculate percentage
    function calculatePercentage() {
        const scored = parseFloat(scoredMarks.value);
        const total = parseFloat(totalMarks.value);
        
        if (!isNaN(scored) && !isNaN(total) && total > 0) {
            // Calculate percentage
            let percentage = (scored / total) * 100;
            
            // Limit to 2 decimal places
            percentage = Math.round(percentage * 100) / 100;
            
            // Update result
            percentageResult.textContent = percentage + '%';
            
            // Add color based on percentage
            updateResultColor(percentage);
            
            // Highlight table row
            highlightTableRow(percentage);
        } else {
            percentageResult.textContent = '0%';
            percentageResult.style.color = '#27ae60';
        }
    }
    
    // Update result color based on percentage
    function updateResultColor(percentage) {
        if (percentage >= 80) {
            percentageResult.style.color = '#27ae60'; // Green for excellent
        } else if (percentage >= 60) {
            percentageResult.style.color = '#2980b9'; // Blue for good
        } else if (percentage >= 40) {
            percentageResult.style.color = '#f39c12'; // Orange for average
        } else {
            percentageResult.style.color = '#e74c3c'; // Red for poor
        }
    }
    
    // Show warning for input validation
    function showInputWarning(input, message) {
        let tooltip = document.createElement('div');
        tooltip.className = 'custom-tooltip';
        tooltip.textContent = message;
        tooltip.style.position = 'absolute';
        tooltip.style.backgroundColor = '#333';
        tooltip.style.color = 'white';
        tooltip.style.padding = '5px 10px';
        tooltip.style.borderRadius = '3px';
        tooltip.style.fontSize = '14px';
        tooltip.style.zIndex = '1000';
        
        // Position the tooltip below the input
        const rect = input.getBoundingClientRect();
        tooltip.style.left = rect.left + 'px';
        tooltip.style.top = (rect.bottom + 5) + 'px';
        
        // Add to document
        document.body.appendChild(tooltip);
        
        // Remove tooltip after 3 seconds
        setTimeout(function() {
            document.body.removeChild(tooltip);
        }, 3000);
    }
    
    // Highlight table row based on percentage
    function highlightTableRow(percentage) {
        // Remove any existing highlights
        const tableRows = document.querySelectorAll('.percentage-table tbody tr');
        tableRows.forEach(row => {
            row.classList.remove('active');
        });
        
        // Find the closest row to highlight
        if (percentage <= 0) return;
        
        const rowPercentages = [10, 20, 30, 40, 50, 60, 70, 80, 90, 100];
        let closestPercentage = rowPercentages[0];
        let minDifference = Math.abs(percentage - closestPercentage);
        
        rowPercentages.forEach(rowPercent => {
            const difference = Math.abs(percentage - rowPercent);
            if (difference < minDifference) {
                minDifference = difference;
                closestPercentage = rowPercent;
            }
        });
        
        // Add highlight to matching row
        const rowIndex = rowPercentages.indexOf(closestPercentage);
        if (rowIndex >= 0 && tableRows[rowIndex]) {
            tableRows[rowIndex].classList.add('active');
        }
    }
    
    calculatePercentage();
});