import requests
from bs4 import BeautifulSoup
import mysql.connector
import time
import json
import re
from urllib.parse import urljoin
from concurrent.futures import ThreadPoolExecutor

class SeriesCrawler:
    def __init__(self):
        self.db = mysql.connector.connect(
            host="localhost",
            user="your_username",
            password="your_password",
            database="video_website"
        )
        self.cursor = self.db.cursor(dictionary=True)
        self.headers = {
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        }
        
    def crawl_series_from_source1(self):
        """从源站点1爬取剧集信息"""
        base_url = "https://example1.com/series"
        
        try:
            response = requests.get(base_url, headers=self.headers)
            soup = BeautifulSoup(response.text, 'html.parser')
            
            series_items = soup.select('.series-item')
            for item in series_items:
                title = item.select_one('.title').text.strip()
                
                # 检查剧集是否已存在
                self.cursor.execute("SELECT id FROM series WHERE title = ?", (title,))
                if self.cursor.fetchone():
                    continue
                
                # 获取详细信息
                detail_url = urljoin(base_url, item.select_one('a')['href'])
                detail_soup = BeautifulSoup(requests.get(detail_url, headers=self.headers).text, 'html.parser')
                
                # 提取信息
                info = {
                    'title': title,
                    'cover_image': item.select_one('img')['src'],
                    'description': detail_soup.select_one('.description').text.strip(),
                    'director': detail_soup.select_one('.director').text.strip(),
                    'actors': detail_soup.select_one('.actors').text.strip(),
                    'area': detail_soup.select_one('.area').text.strip(),
                    'release_year': int(detail_soup.select_one('.year').text.strip()),
                    'category_id': self.get_category_id(detail_soup.select_one('.category').text.strip()),
                    'status': '连载中' if '连载中' in detail_soup.text else '已完结'
                }
                
                # 插入剧集信息
                self.cursor.execute("""
                    INSERT INTO series (title, cover_image, description, director, actors, 
                                     area, release_year, category_id, status)
                    VALUES (%(title)s, %(cover_image)s, %(description)s, %(director)s, 
                           %(actors)s, %(area)s, %(release_year)s, %(category_id)s, %(status)s)
                """, info)
                series_id = self.cursor.lastrowid
                
                # 爬取分集信息
                episodes = detail_soup.select('.episode-list .episode')
                for episode in episodes:
                    episode_info = {
                        'series_id': series_id,
                        'title': episode.select_one('.title').text.strip(),
                        'episode_number': int(re.search(r'\d+', episode.select_one('.number').text).group()),
                        'play_url': self.extract_play_url(episode.select_one('a')['href'])
                    }
                    
                    self.cursor.execute("""
                        INSERT INTO episodes (series_id, title, episode_number, play_url)
                        VALUES (%(series_id)s, %(title)s, %(episode_number)s, %(play_url)s)
                    """, episode_info)
                
                self.db.commit()
                
        except Exception as e:
            print(f"Error crawling series from source1: {e}")
            self.db.rollback()
    
    def extract_play_url(self, page_url):
        """提取播放地址"""
        try:
            response = requests.get(page_url, headers=self.headers)
            soup = BeautifulSoup(response.text, 'html.parser')
            
            # 尝试多种方式提取播放地址
            video_element = soup.select_one('video source')
            if video_element and 'src' in video_element.attrs:
                return video_element['src']
            
            # 从script中提取
            scripts = soup.select('script')
            for script in scripts:
                content = script.string
                if content:
                    # 匹配常见的视频地址模式
                    patterns = [
                        r'playUrl\s*=\s*[\'"]([^\'"]+)[\'"]',
                        r'videoUrl\s*=\s*[\'"]([^\'"]+)[\'"]',
                        r'url:\s*[\'"]([^\'"]+\.(?:mp4|m3u8))[\'"]'
                    ]
                    
                    for pattern in patterns:
                        match = re.search(pattern, content)
                        if match:
                            return match.group(1)
            
            return None
            
        except Exception as e:
            print(f"Error extracting play url: {e}")
            return None
    
    def run(self):
        """运行爬虫"""
        while True:
            print("开始爬取剧集...")
            
            with ThreadPoolExecutor(max_workers=4) as executor:
                executor.submit(self.crawl_series_from_source1)
                # 可以添加更多数据源
                # executor.submit(self.crawl_series_from_source2)
                # executor.submit(self.crawl_series_from_source3)
            
            print("爬取完成，等待下一轮...")
            time.sleep(3600)  # 每小时爬取一次
    
    def __del__(self):
        self.cursor.close()
        self.db.close()

if __name__ == "__main__":
    crawler = SeriesCrawler()
    crawler.run()