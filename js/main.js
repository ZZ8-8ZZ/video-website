// 返回顶部按钮
const backToTopBtn = document.getElementById('backToTop');

window.addEventListener('scroll', () => {
    if (window.pageYOffset > 300) {
        backToTopBtn.classList.add('show');
    } else {
        backToTopBtn.classList.remove('show');
    }
});

backToTopBtn.addEventListener('click', () => {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
});

// 页面加载动画
const pageLoader = document.getElementById('pageLoader');

window.addEventListener('load', () => {
    pageLoader.style.opacity = '0';
    setTimeout(() => {
        pageLoader.style.display = 'none';
    }, 300);
});

// 移动端菜单
const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
const mainNav = document.querySelector('.main-nav');

mobileMenuToggle?.addEventListener('click', () => {
    mainNav.classList.toggle('show');
});

// 面包屑导航生成
function generateBreadcrumb(items) {
    const container = document.querySelector('.breadcrumb-list');
    if (!container) return;
    
    items.forEach((item, index) => {
        const li = document.createElement('li');
        li.className = 'breadcrumb-item';
        
        if (item.url && index < items.length - 1) {
            const a = document.createElement('a');
            a.href = item.url;
            a.textContent = item.text;
            li.appendChild(a);
        } else {
            li.textContent = item.text;
        }
        
        container.appendChild(li);
        
        if (index < items.length - 1) {
            const separator = document.createElement('span');
            separator.className = 'breadcrumb-separator';
            separator.textContent = '/';
            container.appendChild(separator);
        }
    });
}

// 使用示例：
// generateBreadcrumb([
//     { text: '首页', url: '/' },
//     { text: '电影', url: '/category.php?type=movie' },
//     { text: '动作片' }
// ]);