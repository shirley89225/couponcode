# 優惠碼整理網站 (PHP 版本)

這是一個簡單的優惠碼整理網站，使用 PHP 實現，可以從 Google Sheets 獲取資料並顯示。

## 部署到 Zeabur

1. 在 Zeabur 上創建一個新項目
2. 選擇從 GitHub 導入
3. 選擇包含這些文件的倉庫
4. Zeabur 將自動檢測 Dockerfile 並部署 PHP 應用

## 本地開發

1. 安裝 PHP 和 Apache
2. 將文件放在 Apache 的 web 根目錄
3. 訪問 http://localhost 即可

## 使用說明

1. 首次訪問時，需要設置 Google API Key
2. 網站會從 Google Sheets 獲取資料並顯示
3. 可以搜尋、複製優惠碼
4. 可以切換到編輯模式進行資料管理（僅前端，不會實際更新 Google Sheets）
