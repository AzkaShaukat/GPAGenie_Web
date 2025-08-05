document.addEventListener("DOMContentLoaded", () => {
  const letterGradeSelect = document.getElementById("letterGrade")
  const percentGradeSelect = document.getElementById("percentGrade")
  const gpaPointsInput = document.getElementById("gpaPoints")

  const conversionData = [
    { letter: "A+", percentMin: 97, percentMax: 100, gpa: 4.0 },
    { letter: "A", percentMin: 93, percentMax: 96, gpa: 4.0 },
    { letter: "A-", percentMin: 90, percentMax: 92, gpa: 3.7 },
    { letter: "B+", percentMin: 87, percentMax: 89, gpa: 3.3 },
    { letter: "B", percentMin: 83, percentMax: 86, gpa: 3.0 },
    { letter: "B-", percentMin: 80, percentMax: 82, gpa: 2.7 },
    { letter: "C+", percentMin: 77, percentMax: 79, gpa: 2.3 },
    { letter: "C", percentMin: 73, percentMax: 76, gpa: 2.0 },
    { letter: "C-", percentMin: 70, percentMax: 72, gpa: 1.7 },
    { letter: "D+", percentMin: 67, percentMax: 69, gpa: 1.3 },
    { letter: "D", percentMin: 65, percentMax: 66, gpa: 1.0 },
    { letter: "F", percentMin: 0, percentMax: 64, gpa: 0.0 },
  ]

  // Helper function to update percentage select
  function updatePercentSelect(targetPercent) {
    // Clear current selection
    percentGradeSelect.value = ""

    // Find the closest available option
    let closestOption = null
    let minDifference = Number.POSITIVE_INFINITY

    Array.from(percentGradeSelect.options).forEach((option) => {
      if (option.value && option.value !== "") {
        const optionValue = Number.parseInt(option.value)
        const difference = Math.abs(optionValue - targetPercent)
        if (difference < minDifference) {
          minDifference = difference
          closestOption = option
        }
      }
    })

    if (closestOption) {
      percentGradeSelect.value = closestOption.value
    }
  }

  // Helper function to clear all fields
  function clearAllFields() {
    letterGradeSelect.value = ""
    percentGradeSelect.value = ""
    gpaPointsInput.value = ""
    highlightTableRow("")
  }

  // Letter Grade Change
  letterGradeSelect.addEventListener("change", function () {
    if (this.value) {
      const selectedGrade = conversionData.find((item) => item.letter === this.value)
      if (selectedGrade) {
        const avgPercent = Math.round((selectedGrade.percentMin + selectedGrade.percentMax) / 2)

        updatePercentSelect(avgPercent)
        gpaPointsInput.value = selectedGrade.gpa.toFixed(1)

        highlightTableRow(this.value)
      }
    } else {
      // Clear other fields when letter grade is cleared
      percentGradeSelect.value = ""
      gpaPointsInput.value = ""
      highlightTableRow("")
    }
  })

  // Percentage Grade Change
  percentGradeSelect.addEventListener("change", function () {
    if (this.value) {
      const percent = Number.parseInt(this.value)
      if (!isNaN(percent) && percent >= 0 && percent <= 100) {
        const matchedGrade = conversionData.find((item) => percent >= item.percentMin && percent <= item.percentMax)

        if (matchedGrade) {
          letterGradeSelect.value = matchedGrade.letter
          gpaPointsInput.value = matchedGrade.gpa.toFixed(1)

          highlightTableRow(matchedGrade.letter)
        }
      }
    } else {
      // Clear other fields when percentage is cleared
      letterGradeSelect.value = ""
      gpaPointsInput.value = ""
      highlightTableRow("")
    }
  })

  // GPA Points Change
  gpaPointsInput.addEventListener("input", function () {
    const gpaPoints = Number.parseFloat(this.value)
    if (!isNaN(gpaPoints) && gpaPoints >= 0 && gpaPoints <= 4.0) {
      // Find closest GPA match
      let closestGrade = conversionData[0]
      let minDifference = Math.abs(gpaPoints - closestGrade.gpa)

      conversionData.forEach((grade) => {
        const difference = Math.abs(gpaPoints - grade.gpa)
        if (difference < minDifference) {
          minDifference = difference
          closestGrade = grade
        }
      })

      // Update other fields
      letterGradeSelect.value = closestGrade.letter
      const avgPercent = Math.round((closestGrade.percentMin + closestGrade.percentMax) / 2)
      updatePercentSelect(avgPercent)

      // Highlight table row
      highlightTableRow(closestGrade.letter)
    } else if (this.value === "") {
      // Clear other fields when GPA is cleared
      letterGradeSelect.value = ""
      percentGradeSelect.value = ""
      highlightTableRow("")
    }
  })

  // Helper function to highlight table rows
  function highlightTableRow(letter) {
    // Remove any existing highlights
    const tableRows = document.querySelectorAll(".conversion-table tbody tr")
    tableRows.forEach((row) => {
      row.classList.remove("active")
    })

    // Add highlight to matching row
    if (letter) {
      tableRows.forEach((row) => {
        if (row.cells[0].textContent === letter) {
          row.classList.add("active")
        }
      })
    }
  }
})
