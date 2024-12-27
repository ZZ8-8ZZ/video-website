            </main>
        </div>
        
        <!-- 这里是放置模态框的位置 -->
        <div id="modals-container">
            <?php if (isset($page_modals)) echo $page_modals; ?>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
        // 移动端菜单切换
        document.querySelector('.menu-toggle')?.addEventListener('click', () => {
            document.querySelector('.admin-sidebar').classList.toggle('show');
        });
        </script>
    </body>
</html>