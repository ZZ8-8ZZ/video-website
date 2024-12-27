import undetected_chromedriver as uc
import requests
import time
import random
import mysql.connector
import os
import json
from bs4 import BeautifulSoup
from urllib.parse import quote, urlparse
from datetime import datetime
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC

class DoubanCrawler:
    def __init__(self):
        # 数据库配置
        self.db_config = {
            'host': 'localhost',
            'user': 'root',
            'password': 'root',
            'database': 'video_website'
        }
        
        # 配置Chrome选项
        chrome_options = uc.ChromeOptions()
        chrome_options.add_argument('--headless')
        chrome_options.add_argument('--no-sandbox')
        chrome_options.add_argument('--disable-dev-shm-usage')
        chrome_options.binary_location = r"C:\Program Files\Google\Chrome\Application\chrome.exe"
        
        try:
            self.driver = uc.Chrome(options=chrome_options)
            self.wait = WebDriverWait(self.driver, 10)
            print("Browser initialized successfully")
        except Exception as e:
            print(f"Error initializing Chrome driver: {str(e)}")
            raise
        
        # 配置请求头
        self.headers = {
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language': 'zh-CN,zh;q=0.9,en;q=0.8'
        }
        
        # 配置参数
        self.config = {
            'image_path': 'public/uploads/covers/',
            'checkpoint_file': 'crawler_checkpoint.json',
            'max_retries': 3,
            'items_per_type': 50,
        }
        
        os.makedirs(self.config['image_path'], exist_ok=True)
        self.checkpoint = self.load_checkpoint()

    def __del__(self):
        if hasattr(self, 'driver'):
            self.driver.quit()

    def load_checkpoint(self):
        try:
            if os.path.exists(self.config['checkpoint_file']):
                with open(self.config['checkpoint_file'], 'r') as f:
                    return json.load(f)
        except Exception as e:
            print(f"Error loading checkpoint: {str(e)}")
        return {'crawled_urls': [], 'last_position': {}}

    def save_checkpoint(self):
        try:
            with open(self.config['checkpoint_file'], 'w') as f:
                json.dump(self.checkpoint, f)
        except Exception as e:
            print(f"Error saving checkpoint: {str(e)}")

    def download_image(self, url, title):
        """下载图片并返回本地路径"""
        try:
            # 生成文件名
            ext = os.path.splitext(urlparse(url).path)[1]
            if not ext:
                ext = '.jpg'
            filename = f"{int(time.time())}_{title.replace(' ', '_')}{ext}"
            filepath = os.path.join(self.config['image_path'], filename)
            
            # 下载图片
            response = requests.get(url, headers=self.headers)
            response.raise_for_status()
            
            with open(filepath, 'wb') as f:
                f.write(response.content)
            
            return filepath.replace('public/', '')  # 返回数据库中存储的相对路径
            
        except Exception as e:
            print(f"Error downloading image {url}: {str(e)}")
            return url  # 如果下载失败，返回原始URL

    def is_item_exists(self, title, content_type):
        """检查数据是否已存在"""
        conn = self.connect_db()
        cursor = conn.cursor()
        
        try:
            cursor.execute(
                "SELECT id FROM series WHERE title = %s AND type = %s",
                (title, content_type)
            )
            return cursor.fetchone() is not None
        finally:
            cursor.close()
            conn.close()

    def get_page_with_retry(self, url):
        """使用 Selenium 获取页面内容"""
        for i in range(self.config['max_retries']):
            try:
                if url in self.checkpoint['crawled_urls']:
                    print(f"Skip crawled url: {url}")
                    return None
                
                print(f"Attempting to load URL: {url}")
                self.driver.get(url)
                print("Page loaded, waiting for body element...")
                
                # 等待页面加载完成
                self.wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
                print("Body element found, waiting additional time...")
                
                time.sleep(5)  # 增加等待时间
                
                self.checkpoint['crawled_urls'].append(url)
                self.save_checkpoint()
                
                print("Page loaded successfully")
                return self.driver.page_source
                
            except Exception as e:
                print(f"Attempt {i+1} failed for {url}: {str(e)}")
                if i < self.config['max_retries'] - 1:
                    time.sleep(random.uniform(5, 10))  # 增加重试等待时间
                    continue
                return None

    def connect_db(self):
        return mysql.connector.connect(**self.db_config)

    def parse_movie_tv(self, html, content_type):
        soup = BeautifulSoup(html, 'html.parser')
        results = []
        count = 0
        
        if content_type == 'movie':
            items = soup.select('.grid-view .item')
        else:
            items = soup.select('.list-wp .item')
        
        for item in items:
            if count >= self.config['items_per_type']:
                break
                
            try:
                # 获取基本信息
                if content_type == 'movie':
                    title_elem = item.select_one('.title a')
                    title = title_elem.text.strip()
                    detail_url = title_elem['href']
                    cover_url = item.select_one('img')['src'].replace('s_ratio', 'l_ratio')
                    rating = item.select_one('.rating_num')
                    rating = float(rating.text.strip()) if rating else 0.0
                else:
                    title_elem = item.select_one('a.title')
                    title = title_elem.text.strip()
                    detail_url = title_elem['href']
                    cover_url = item.select_one('img')['src'].replace('s_ratio', 'l_ratio')
                    rating = item.select_one('.rating')
                    rating = float(rating.text.strip()) if rating else 0.0

                # 检查是否已存在
                if self.is_item_exists(title, content_type):
                    print(f"Skip existing item: {title}")
                    continue

                # 获取详情页
                detail_html = self.get_page_with_retry(detail_url)
                if detail_html:
                    detail_soup = BeautifulSoup(detail_html, 'html.parser')
                    info = detail_soup.select_one('#info')
                    description = detail_soup.select_one('[property="v:summary"]')
                    if not description:
                        description = detail_soup.select_one('.indent')

                    # 下载封面图片
                    cover_path = self.download_image(cover_url, title)

                    results.append({
                        'title': title,
                        'cover_image': cover_path,
                        'description': description.text.strip() if description else '',
                        'director': self.extract_info(info, '导演'),
                        'actors': self.extract_info(info, '主演'),
                        'area': self.extract_info(info, '制片国家/地区'),
                        'release_year': self.extract_info(info, '年份'),
                        'rating': rating,
                        'type': content_type,
                        'status': 'completed' if content_type == 'movie' else 'ongoing'
                    })

                    count += 1
                    print(f"Successfully parsed: {title}")

                time.sleep(random.uniform(2, 5))

            except Exception as e:
                print(f"Error parsing item: {str(e)}")
                continue

        return results

    def extract_info(self, info_element, label):
        if not info_element:
            return None
        text = str(info_element)
        start = text.find(label + ': ')
        if start == -1:
            return None
        start += len(label) + 2
        end = text.find('<br/>', start)
        return text[start:end].strip() if end != -1 else text[start:].strip()

    def save_to_db(self, items):
        conn = self.connect_db()
        cursor = conn.cursor()
        
        insert_query = """
        INSERT INTO series 
        (title, description, cover_image, director, actors, area, 
         release_year, rating, type, status, created_at)
        VALUES 
        (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, NOW())
        """
        
        try:
            for item in items:
                cursor.execute(insert_query, (
                    item['title'],
                    item['description'],
                    item['cover_image'],
                    item['director'],
                    item['actors'],
                    item['area'],
                    item['release_year'],
                    item['rating'],
                    item['type'],
                    item['status']
                ))
            
            conn.commit()
            print(f"Successfully saved {len(items)} items")
            
        except Exception as e:
            conn.rollback()
            print(f"Error saving to database: {str(e)}")
            
        finally:
            cursor.close()
            conn.close()

    def crawl(self):
        content_types = {
            'movie': {
                'url': 'https://movie.douban.com/top250',
                'pages': range(0, 1)  # 先只爬取1页测试
            }
        }
        
        try:
            for content_type, config in content_types.items():
                print(f"\nCrawling {content_type}...")
                
                start_page = self.checkpoint['last_position'].get(content_type, 0)
                
                for page in config['pages'][start_page:]:
                    url = f"{config['url']}?start={page*25}"
                    print(f"Crawling page {page+1} from {url}")
                    
                    html = self.get_page_with_retry(url)
                    if html:
                        print("Got page content, parsing items...")
                        items = self.parse_movie_tv(html, content_type)
                        if items:
                            self.save_to_db(items)
                            print(f"Saved {len(items)} items from page {page+1}")
                        
                        self.checkpoint['last_position'][content_type] = page + 1
                        self.save_checkpoint()
                    
                    time.sleep(random.uniform(5, 10))
                    
        except KeyboardInterrupt:
            print("\nCrawling interrupted. Progress saved.")
            self.save_checkpoint()
        except Exception as e:
            print(f"Error during crawling: {str(e)}")
            self.save_checkpoint()
        finally:
            self.driver.quit()

if __name__ == '__main__':
    crawler = DoubanCrawler()
    crawler.crawl() 