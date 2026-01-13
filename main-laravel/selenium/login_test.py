from selenium import webdriver
from selenium.webdriver.common.by import By
import time

driver = webdriver.Chrome()
driver.get("http://127.0.0.1:8000/login")
time.sleep(2)

email = driver.find_element(By.NAME, "email")
password = driver.find_element(By.NAME, "password")

email.send_keys("randomuser@test.com")
password.send_keys("123456")

time.sleep(3)
driver.quit()
