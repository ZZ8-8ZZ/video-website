import requests
from bs4 import BeautifulSoup
import mysql.connector
import time
import json
import re
from urllib.parse import urljoin

class VideoCrawler:
    def __init__(self):
        self.db = mysql.connector.connect(
            host="localhost",
            user="your_username",
            password="your_password",
            database="video_website"
        )
        self.cursor = self.db.cursor(dictionary=True)
        self.headers = {
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
        }
        
    def get_category_id(self, category_name, category_type):
        # 检查分类是否存在
        self.cursor.execute("SELECT id FROM categories WHERE name = %s AND type = %s", 
                          (category_name, category_type))
        result = self.cursor.fetchone()
        
        if result:
            return result['id']
        
        # 如果不存在，创建新分类
        self.cursor.execute("INSERT INTO categories (name, type) VALUES (%s, %s)",
                          (category_name, category_type))
        self.db.commit()
        return self.cursor.lastrowid
    
    def crawl_from_source1(self):
        """爬取源站点1的视频"""
        base_url = "https://example1.com/videos"
        
        try:
            response = requests.get(base_url, headers=self.headers)
            soup = BeautifulSoup(response.text, 'html.parser')
            
            video_items = soup.select('.video-item')
            for item in video_items:
                title = item.select_one('.title').text.strip()
                
                # 检查视频是否已存在
                self.cursor.execute("SELECT id FROM videos WHERE title = %s", (title,))
                if self.cursor.fetchone():
                    continue
                
                # 获取视频详情
                detail_url = urljoin(base_url, item.select_one('a')['href'])
                detail_response = requests.get(detail_url, headers=self.headers)
                detail_soup = BeautifulSoup(detail_response.text, 'html.parser')
                
                category_name = detail_soup.select_one('.category').text.strip()
                category_type = self.determine_category_type(category_name)
                category_id = self.get_category_id(category_name, category_type)
                
                video_data = {
                    'title': title,
                    'category_id': category_id,
                    'cover_image': item.select_one('img')['src'],
                    'description': detail_soup.select_one('.description').text.strip(),
                    'source_url': detail_url,
                    'play_url': self.extract_play_url(detail_soup)
                }
                
                # 插入视频信息
                sql = """INSERT INTO videos 
                        (title, category_id, cover_image, description, source_url, play_url)
                        VALUES (%(title)s, %(category_id)s, %(cover_image)s, 
                                %(description)s, %(source_url)s, %(play_url)s)"""
                self.cursor.execute(sql, video_data)
                self.db.commit()
                
        except Exception as e:
            print(f"Error crawling source1: {str(e)}")
    
    def crawl_from_source2(self):
        """爬取源站点2的视频"""
        api_url = "https://example2.com/api/videos"
        
        try:
            response = requests.get(api_url, headers=self.headers)
            data = response.json()
            
            for video in data['videos']:
                # 检查视频是否已存在
                self.cursor.execute("SELECT id FROM videos WHERE title = %s", (video['title'],))
                if self.cursor.fetchone():
                    continue
                
                category_id = self.get_category_id(video['category'], video['type'])
                
                video_data = {
                    'title': video['title'],
                    'category_id': category_id,
                    'cover_image': video['cover'],
                    'description': video['description'],
                    'source_url': video['url'],
                    'play_url': video['play_url']
                }
                
                sql = """INSERT INTO videos 
                        (title, category_id, cover_image, description, source_url, play_url)
                        VALUES (%(title)s, %(category_id)s, %(cover_image)s, 
                                %(description)s, %(source_url)s, %(play_url)s)"""
                self.cursor.execute(sql, video_data)
                self.db.commit()
                
        except Exception as e:
            print(f"Error crawling source2: {str(e)}")
    
    def determine_category_type(self, category_name):
        """根据分类名称判断类型"""
        movie_keywords = ['电影', '影片', 'movie']
        tv_keywords = ['电视剧', '剧集', 'tv']
        variety_keywords = ['综艺', '真人秀', 'show']
        anime_keywords = ['动漫', '动画', 'anime']
        
        category_name = category_name.lower()
        
        if any(keyword in category_name for keyword in movie_keywords):
            return 'movie'
        elif any(keyword in category_name for keyword in tv_keywords):
            return 'tv'
        elif any(keyword in category_name for keyword in variety_keywords):
            return 'variety'
        elif any(keyword in category_name for keyword in anime_keywords):
            return 'anime'
        else:
            return 'movie'  # 默认为电影
    
    def extract_play_url(self, soup):
        """提取播放地址"""
        # 这里需要根据具体网站来实现
        # 示例：从页面中提取视频地址
        video_element = soup.select_one('video source')
        if video_element and 'src' in video_element.attrs:
            return video_element['src']
        
        # 或者从script标签中提取
        scripts = soup.select('script')
        for script in scripts:
            content = script.string
            if content:
                # 使用正则表达式匹配视频地址
                match = re.search(r'playUrl\s*=\s*[\'"]([^\'"]+)[\'"]', content)
                if match:
                    return match.group(1)
        
        return None
    
    def run(self):
        """运行爬虫"""
        while True:
            print("开始爬取视频...")
            self.crawl_from_source1()
            self.crawl_from_source2()
            print("爬取完成，等待下一轮...")
            time.sleep(3600)  # 每小时爬取一次
    
    def __del__(self):
        self.cursor.close()
        self.db.close()

if __name__ == "__main__":
    crawler = VideoCrawler()
    crawler.run()