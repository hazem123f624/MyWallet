// Smooth Active Navigation State
const scrollContainer = document.querySelector('.content-scroll');
const sections = document.querySelectorAll('.section-wrapper');
const navItems = document.querySelectorAll('.nav-item');

if (scrollContainer) {
    scrollContainer.addEventListener('scroll', () => {
        let current = '';
        sections.forEach(section => {
            const sectionTop = section.offsetTop;
            if (scrollContainer.scrollTop >= sectionTop - 150) {
                current = section.getAttribute('id');
            }
        });

        navItems.forEach(item => {
            item.classList.remove('active');
            if (item.getAttribute('href').includes(current)) {
                item.classList.add('active');
            }
        });
    });
}

// Search Functionality
const apiSearch = document.getElementById('apiSearch');
if (apiSearch) {
    apiSearch.addEventListener('input', function (e) {
        const term = e.target.value.toLowerCase();
        const items = document.querySelectorAll('.nav-api-item');

        items.forEach(item => {
            const text = item.textContent.toLowerCase();
            if (text.includes(term)) {
                item.style.display = 'flex';
            } else {
                item.style.display = 'none';
            }
        });
    });
}

// Copy to Clipboard
function copyCode(btn) {
    const pre = btn.parentElement.nextElementSibling.querySelector('code');
    const text = pre.textContent;

    navigator.clipboard.writeText(text).then(() => {
        const originalText = btn.textContent;
        btn.textContent = 'Copied!';
        btn.style.borderColor = '#10b981';
        btn.style.color = '#10b981';

        setTimeout(() => {
            btn.textContent = originalText;
            btn.style.borderColor = '';
            btn.style.color = '';
        }, 2000);
    });
}

// Dropdown Toggle
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.dropdown-toggle').forEach(button => {
        button.addEventListener('click', () => {
            const targetId = button.getAttribute('data-target');
            const content = document.getElementById(targetId);
            const section = button.closest('.dropdown-section');

            if (content) {
                content.classList.toggle('show');
                section.classList.toggle('active');
            }
        });
    });
});
