<?php
/**
 * Navigation components for Luntiang H.A.P.A.G.
 * Reusable navigation elements including breadcrumbs and back-to-top button
 */

/**
 * Render breadcrumb navigation
 * Usage: renderBreadcrumbs(['Home' => 'index.php', 'Current Page' => ''])
 */
function renderBreadcrumbs($items = []) {
    if (empty($items)) return;
    ?>
    <nav aria-label="Breadcrumb" class="mb-6 max-w-7xl mx-auto px-6 lg:px-8 pt-4">
        <ol class="flex flex-wrap items-center gap-1 text-sm">
            <li>
                <a href="index.php" class="text-[#17611f] hover:opacity-80 transition-opacity flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    Home
                </a>
            </li>
            <?php 
            $total = count($items);
            $i = 0;
            foreach ($items as $label => $url): 
                $i++;
                $isLast = $i === $total;
            ?>
                <li>
                    <span class="text-gray-400 mx-1">/</span>
                </li>
                <li>
                    <?php if ($isLast || empty($url)): ?>
                        <span class="text-gray-600 font-medium"><?= htmlspecialchars($label) ?></span>
                    <?php else: ?>
                        <a href="<?= htmlspecialchars($url) ?>" class="text-[#17611f] hover:opacity-80 transition-opacity">
                            <?= htmlspecialchars($label) ?>
                        </a>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ol>
    </nav>
    <?php
}

/**
 * Render Back to Top button
 * Positioned above the chat widget on the right side
 */
function renderBackToTop() {
    ?>
    <!-- Back to Top Button - Above Chat Widget -->
    <button id="backToTopBtn" 
            class="fixed bottom-28 right-8 z-[9999] w-12 h-12 rounded-full bg-[#17611f] text-white shadow-lg hover:opacity-90 transition-all duration-300 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-[#17611f]/40 flex items-center justify-center"
            aria-label="Back to top"
            style="opacity: 0; visibility: hidden; transform: translateY(20px); transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/>
        </svg>
    </button>

    <script>
        (function() {
            'use strict';
            
            function initBackToTop() {
                const btn = document.getElementById('backToTopBtn');
                if (!btn) return;

                let isVisible = false;
                let scrollTimeout;

                function toggleButton(show) {
                    if (show === isVisible) return;
                    isVisible = show;
                    
                    if (show) {
                        btn.style.opacity = '1';
                        btn.style.visibility = 'visible';
                        btn.style.transform = 'translateY(0)';
                    } else {
                        btn.style.opacity = '0';
                        btn.style.visibility = 'hidden';
                        btn.style.transform = 'translateY(20px)';
                    }
                }

                function handleScroll() {
                    if (scrollTimeout) {
                        cancelAnimationFrame(scrollTimeout);
                    }

                    scrollTimeout = requestAnimationFrame(function() {
                        const scrollY = window.scrollY || window.pageYOffset;
                        
                        if (scrollY > 300) {
                            toggleButton(true);
                        } else {
                            toggleButton(false);
                        }
                    });
                }

                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    window.scrollTo({
                        top: 0,
                        behavior: 'smooth'
                    });
                });

                window.addEventListener('scroll', handleScroll, { passive: true });
                window.addEventListener('resize', handleScroll, { passive: true });

                handleScroll();
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initBackToTop);
            } else {
                initBackToTop();
            }
        })();
    </script>
    <?php
}
?>