(function () {
    "use strict";

    const sidebar = document.getElementById("sidebar");
    const backdrop = document.getElementById("sidebarBackdrop");
    const menuButton = document.getElementById("menuButton");
    const closeButton = document.getElementById("sidebarClose");
    const content = document.getElementById("mainContent");
    const searchInput = document.getElementById("apiSearch");
    const searchEmpty = document.getElementById("searchEmpty");
    const backToTop = document.getElementById("backToTop");
    const themeToggle = document.getElementById("themeToggle");

    const getStoredTheme = () => {
        try {
            return window.localStorage.getItem("mywallet-docs-theme");
        } catch (error) {
            return null;
        }
    };

    const applyTheme = (theme) => {
        const nextTheme = theme === "dark" ? "dark" : "light";

        document.documentElement.dataset.theme = nextTheme;

        if (themeToggle) {
            themeToggle.setAttribute("aria-pressed", nextTheme === "dark" ? "true" : "false");
            themeToggle.setAttribute(
                "aria-label",
                nextTheme === "dark" ? "Switch to light mode" : "Switch to night mode"
            );
        }
    };

    const storedTheme = getStoredTheme();

    applyTheme(storedTheme || document.documentElement.dataset.theme || "light");

    themeToggle?.addEventListener("click", () => {
        const nextTheme = document.documentElement.dataset.theme === "dark" ? "light" : "dark";

        try {
            window.localStorage.setItem("mywallet-docs-theme", nextTheme);
        } catch (error) {
            console.warn("Unable to save the theme preference", error);
        }

        applyTheme(nextTheme);
    });

    const openSidebar = () => {
        if (!sidebar || !backdrop || !menuButton) {
            return;
        }

        sidebar.classList.add("open");
        backdrop.classList.add("visible");
        menuButton.setAttribute("aria-expanded", "true");
    };

    const closeSidebar = () => {
        if (!sidebar || !backdrop || !menuButton) {
            return;
        }

        sidebar.classList.remove("open");
        backdrop.classList.remove("visible");
        menuButton.setAttribute("aria-expanded", "false");
    };

    menuButton?.addEventListener("click", openSidebar);
    closeButton?.addEventListener("click", closeSidebar);
    backdrop?.addEventListener("click", closeSidebar);

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape") {
            closeSidebar();
        }
    });

    sidebar?.querySelectorAll("a[href^='#']").forEach((link) => {
        link.addEventListener("click", () => {
            if (window.matchMedia("(max-width: 900px)").matches) {
                closeSidebar();
            }
        });
    });

    // Search and method filters endpoint cards and sidebar links together.
    const methodButtons = Array.from(document.querySelectorAll("[data-method-filter]"));
    let activeMethod = "all";

    const filterEndpoints = () => {
        const term = searchInput ? searchInput.value.trim().toLowerCase() : "";
        const filterActive = term !== "" || activeMethod !== "all";
        const cards = Array.from(document.querySelectorAll(".endpoint-card"));
        const navLinks = Array.from(document.querySelectorAll(".nav-endpoint"));
        const matchesFilter = (element) => {
            const method = (element.dataset.method || "").toLowerCase();
            const haystack = element.dataset.search || "";
            const matchesMethod = activeMethod === "all" || method === activeMethod;
            const matchesSearch = term === "" || haystack.includes(term);

            return matchesMethod && matchesSearch;
        };

        cards.forEach((card) => {
            card.hidden = !matchesFilter(card);
        });

        navLinks.forEach((link) => {
            link.hidden = !matchesFilter(link);
        });

        document.querySelectorAll(".nav-module").forEach((module) => {
            const visibleLinks = module.querySelectorAll(".nav-endpoint:not([hidden])").length;
            module.hidden = filterActive && visibleLinks === 0;

            if (filterActive && visibleLinks > 0) {
                module.open = true;
            }
        });

        document.querySelectorAll(".nav-group").forEach((group) => {
            if (group.querySelector(".nav-endpoint")) {
                const visibleLinks = group.querySelectorAll(".nav-endpoint:not([hidden])").length;
                group.hidden = filterActive && visibleLinks === 0;
            }
        });

        document.querySelectorAll(".module-block").forEach((block) => {
            const visibleCards = block.querySelectorAll(".endpoint-card:not([hidden])").length;
            block.hidden = filterActive && visibleCards === 0;
        });

        document.querySelectorAll(".section[id^='consumer-']").forEach((section) => {
            const visibleCards = section.querySelectorAll(".endpoint-card:not([hidden])").length;
            section.hidden = filterActive && visibleCards === 0;
        });

        if (searchEmpty) {
            searchEmpty.hidden = !filterActive || cards.some((card) => !card.hidden);
        }
    };

    methodButtons.forEach((button) => {
        button.addEventListener("click", () => {
            activeMethod = button.dataset.methodFilter || "all";

            methodButtons.forEach((item) => {
                const selected = item === button;
                item.classList.toggle("active", selected);
                item.setAttribute("aria-pressed", selected ? "true" : "false");
            });

            filterEndpoints();
        });
    });

    searchInput?.addEventListener("input", filterEndpoints);

    // Highlight the navigation item for the section currently in view.
    const navLinks = Array.from(document.querySelectorAll(".nav-link[href^='#']"));
    const targets = navLinks
        .map((link) => {
            const id = link.getAttribute("href").slice(1);
            const element = document.getElementById(id);

            return element ? { link, element } : null;
        })
        .filter(Boolean);

    let ticking = false;

    const updateActiveLink = () => {
        if (!content || targets.length === 0) {
            return;
        }

        const contentTop = content.getBoundingClientRect().top;
        let current = null;

        targets.forEach((target) => {
            if (target.link.hidden || target.element.hidden) {
                return;
            }

            const rect = target.element.getBoundingClientRect();

            if (rect.top - contentTop <= 140) {
                current = target.link;
            }
        });

        navLinks.forEach((link) => link.classList.toggle("active", link === current));
    };

    const onScroll = () => {
        if (ticking) {
            return;
        }

        ticking = true;

        window.requestAnimationFrame(() => {
            updateActiveLink();

            const scrolled = Math.max(
                content ? content.scrollTop : 0,
                window.scrollY || document.documentElement.scrollTop
            );

            backToTop?.classList.toggle("visible", scrolled > 420);
            ticking = false;
        });
    };

    content?.addEventListener("scroll", onScroll, { passive: true });
    window.addEventListener("scroll", onScroll, { passive: true });
    updateActiveLink();

    // Copy buttons for request and response examples.
    document.addEventListener("click", (event) => {
        const button = event.target.closest("[data-copy]");

        if (!button) {
            return;
        }

        const box = button.closest(".code-box");
        const code = box?.querySelector("code");

        if (!code) {
            return;
        }

        const text = code.textContent;

        const copied = () => {
            const original = button.textContent;
            button.textContent = "Copied!";
            button.classList.add("copied");

            window.setTimeout(() => {
                button.textContent = original;
                button.classList.remove("copied");
            }, 1600);
        };

        if (navigator.clipboard?.writeText) {
            navigator.clipboard.writeText(text).then(copied).catch(() => {});
            return;
        }

        const textarea = document.createElement("textarea");
        textarea.value = text;
        textarea.style.position = "fixed";
        textarea.style.opacity = "0";
        document.body.appendChild(textarea);
        textarea.select();

        try {
            document.execCommand("copy");
            copied();
        } finally {
            textarea.remove();
        }
    });

    backToTop?.addEventListener("click", () => {
        content?.scrollTo({ top: 0, behavior: "smooth" });
        window.scrollTo({ top: 0, behavior: "smooth" });
    });
})();
