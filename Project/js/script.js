document.addEventListener('DOMContentLoaded', function() {
// --- Page Aliases for Search ---
const pageMap = [
  { terms: ['sgpa', 'semester gpa', 'semester grade'], page: 'sgpa.html' },
  { terms: ['cgpa', 'cumulative gpa', 'overall gpa'], page: 'cgpa.html' },
  { terms: ['grade', 'grades'], page: 'grade.html' },
  { terms: ['converter', 'grade converter', 'gpa converter'], page: 'converter.html' },
  { terms: ['percentage', 'gpa to percentage'], page: 'percentage.html' },
  { terms: ['about', 'about us'], page: 'about.html' },
  { terms: ['login', 'sign in'], page: 'login.html' },
  { terms: ['home', 'main'], page: 'homepage.html' }
];

// --- Search Functionality ---
function initSearch(input) {
  if (!input) return;
  const form = input.closest('form');
  if (!form) return;

  let dropdown = form.querySelector('.search-dropdown');
  if (!dropdown) {
    dropdown = document.createElement('div');
    dropdown.className = 'search-dropdown';
    if (getComputedStyle(form).position === 'static') form.style.position = 'relative';
    form.appendChild(dropdown);
  }

  input.addEventListener('input', () => {
    const term = input.value.trim().toLowerCase();
    dropdown.innerHTML = '';
    if (!term) return dropdown.style.display = 'none';

    const matches = pageMap.filter(p => p.terms.some(t => t.includes(term))).slice(0, 5);
    if (!matches.length) return dropdown.style.display = 'none';

    matches.forEach(match => {
      const item = document.createElement('div');
      item.className = 'search-dropdown-item';
      item.textContent = match.terms[0];
      item.onclick = () => location.href = match.page;
      dropdown.appendChild(item);
    });

    dropdown.style.display = 'block';
  });

  input.addEventListener('keydown', e => {
    if (e.key === 'Enter') {
      e.preventDefault();
      const term = input.value.trim().toLowerCase();
      const match = pageMap.find(p => p.terms.some(t => t === term)) || pageMap.find(p => p.terms.some(t => t.includes(term)));
      if (match) location.href = match.page;
      else dropdown.style.display = 'none';
    }
  });

  document.addEventListener('click', e => {
    if (!form.contains(e.target)) dropdown.style.display = 'none';
  });

  form.addEventListener('click', e => e.stopPropagation());
}

// Initialize desktop and mobile search
initSearch(document.getElementById('searchInputDesktop'));
initSearch(document.getElementById('searchInputMobile'));

// --- Navbar Toggle ---
const navToggleBtn = document.querySelector('.navbar-toggle[data-target]');
const navTarget = navToggleBtn && document.querySelector(navToggleBtn.getAttribute('data-target'));
const mobileSearchForm = document.getElementById('searchFormMobile');
const searchIconBtn = document.getElementById('searchIconToggleMobile');

if (navToggleBtn && navTarget) {
  navToggleBtn.onclick = () => {
    const navOpen = navTarget.classList.toggle('navbar-visible');
    navToggleBtn.setAttribute('aria-expanded', navOpen);

    if (navOpen && mobileSearchForm?.classList.contains('active')) {
      mobileSearchForm.classList.remove('active');
      searchIconBtn?.setAttribute('aria-expanded', 'false');
    }
  };
}

// --- Mobile Search Toggle ---
if (searchIconBtn && mobileSearchForm) {
  searchIconBtn.onclick = e => {
    e.stopPropagation();
    const active = mobileSearchForm.classList.toggle('active');
    searchIconBtn.setAttribute('aria-expanded', active);
    if (active) {
      document.getElementById('searchInputMobile')?.focus();
      if (navTarget?.classList.contains('navbar-visible')) {
        navTarget.classList.remove('navbar-visible');
        navToggleBtn?.setAttribute('aria-expanded', 'false');
      }
    }
  };

  document.addEventListener('click', e => {
    if (
      mobileSearchForm.classList.contains('active') &&
      !mobileSearchForm.contains(e.target) &&
      !searchIconBtn.contains(e.target)
    ) {
      mobileSearchForm.classList.remove('active');
      searchIconBtn.setAttribute('aria-expanded', 'false');
    }
  });
}



    // --- Dropdown menu functionality for "Get Started" button ---
    const dropdownButton = document.getElementById('getStartedButton');
    const getStartedDropdownMenu = document.querySelector('.dropdown-menu[aria-labelledby="getStartedButton"]'); 
    
    if (dropdownButton && getStartedDropdownMenu) {
        dropdownButton.setAttribute('aria-haspopup', 'true'); 
        dropdownButton.setAttribute('aria-expanded', 'false');

        dropdownButton.addEventListener('click', function(e) {
            e.stopPropagation(); 
            
            getStartedDropdownMenu.classList.toggle('show');
            this.setAttribute('aria-expanded', getStartedDropdownMenu.classList.contains('show').toString());
        });

        getStartedDropdownMenu.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }
});
