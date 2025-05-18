<?php
// 設定
$spreadsheetId = '1vvSvuBROuG3MXIX5xZy0hiyeeYGlOoRMrqH0QJbS1Vs';
$sheetName = 'sheet1';
$apiKey = ''; // 請在 config.php 中設定您的 API Key

// 載入設定檔
if (file_exists('config.php')) {
    include 'config.php';
}

// 獲取資料函數
function fetchSheetData($spreadsheetId, $sheetName, $apiKey) {
    $url = "https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheetId}/values/{$sheetName}?alt=json&key={$apiKey}";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['error' => "cURL Error: {$error}"];
    }
    
    $data = json_decode($response, true);
    
    if (isset($data['error'])) {
        return ['error' => "API Error: {$data['error']['message']}"];
    }
    
    if (!isset($data['values']) || count($data['values']) < 1) {
        return ['error' => "No data found"];
    }
    
    $headers = $data['values'][0];
    $rows = array_slice($data['values'], 1);
    
    $formattedData = [];
    foreach ($rows as $row) {
        $item = [];
        foreach ($headers as $index => $header) {
            $item[$header] = isset($row[$index]) ? $row[$index] : '';
        }
        $formattedData[] = $item;
    }
    
    return [
        'headers' => $headers,
        'data' => $formattedData
    ];
}

// 處理表單提交
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'save_api_key':
                if (isset($_POST['api_key']) && !empty($_POST['api_key'])) {
                    $newApiKey = $_POST['api_key'];
                    $configContent = "<?php\n\$apiKey = '{$newApiKey}';\n";
                    
                    if (file_put_contents('config.php', $configContent)) {
                        $apiKey = $newApiKey;
                        $message = "API Key 已成功儲存！";
                        $messageType = "success";
                    } else {
                        $message = "無法寫入設定檔，請確認檔案權限。";
                        $messageType = "danger";
                    }
                }
                break;
                
            case 'add_data':
            case 'edit_data':
                // 注意：此簡單版本不會實際更新 Google Sheets
                $message = "資料已在前端更新，但未實際儲存到 Google Sheets。若要永久儲存變更，請實作 Google Sheets API 寫入功能。";
                $messageType = "warning";
                break;
        }
    }
}

// 獲取資料
$result = [];
if (!empty($apiKey)) {
    $result = fetchSheetData($spreadsheetId, $sheetName, $apiKey);
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>優惠碼整理 | 最新折扣碼</title>
    <meta name="description" content="尋找並分享各大網站的優惠碼、折扣碼、推薦碼，獲得最新優惠">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .copy-btn {
            cursor: pointer;
            background: none;
            border: none;
            color: #0d6efd;
        }
        .copy-btn:hover {
            color: #0a58ca;
        }
        .copy-success {
            color: #198754;
        }
        #editForm {
            display: none;
        }
        .admin-panel {
            background-color: #f8f9fa;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <h1 class="text-center mb-4">最新優惠券、推薦碼、邀請連結整理合輯</h1>
        
        <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <!-- 管理員面板 -->
        <div class="admin-panel mb-4">
            <h2>管理面板</h2>
            
            <?php if (empty($apiKey)): ?>
            <div class="card p-3">
                <h3>設定 Google API Key</h3>
                <p>請輸入您的 Google API Key 以訪問資料：</p>
                <form method="post" action="">
                    <input type="hidden" name="action" value="save_api_key">
                    <div class="mb-3">
                        <input type="text" name="api_key" class="form-control" placeholder="輸入 Google API Key" required>
                    </div>
                    <button type="submit" class="btn btn-primary">儲存 API Key</button>
                </form>
            </div>
            <?php else: ?>
            <button id="toggleEditMode" class="btn btn-primary mb-3">切換編輯模式</button>
            <button id="addNewRow" class="btn btn-success mb-3 ms-2" style="display: none;">新增資料</button>
            
            <div id="editForm" class="card p-3 mb-3">
                <h3 id="formTitle">編輯資料</h3>
                <form id="dataForm">
                    <input type="hidden" id="rowIndex" value="">
                    <div id="formFields" class="mb-3">
                        <!-- 動態生成的表單欄位 -->
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">儲存</button>
                        <button type="button" id="cancelEdit" class="btn btn-secondary">取消</button>
                    </div>
                </form>
            </div>
            <?php endif; ?>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>查找並分享使用優惠</h2>
                <p class="text-muted">查詢你最喜歡的網站/服務的最新優惠</p>
                <div class="input-group mt-3">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" id="searchInput" class="form-control" placeholder="搜尋優惠碼...">
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($apiKey)): ?>
                <div class="alert alert-info">
                    請先在管理面板設定 Google API Key。
                </div>
                <?php elseif (isset($result['error'])): ?>
                <div class="alert alert-danger">
                    載入資料失敗：<?php echo $result['error']; ?>
                </div>
                <?php elseif (empty($result['data'])): ?>
                <div class="alert alert-warning">
                    沒有找到資料。
                </div>
                <?php else: ?>
                <div id="dataContainer">
                    <h2 class="mb-3">可用的優惠</h2>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <?php foreach ($result['headers'] as $header): ?>
                                    <th><?php echo htmlspecialchars($header); ?></th>
                                    <?php endforeach; ?>
                                    <th class="action-column" style="display: none;">操作</th>
                                </tr>
                            </thead>
                            <tbody id="tableBody">
                                <?php foreach ($result['data'] as $index => $row): ?>
                                <tr>
                                    <?php foreach ($result['headers'] as $header): ?>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <?php 
                                            $value = isset($row[$header]) ? $row[$header] : '';
                                            $isUrl = filter_var($value, FILTER_VALIDATE_URL);
                                            
                                            if ($isUrl && $header === '官網連結'): 
                                            ?>
                                                <a href="<?php echo htmlspecialchars($value); ?>" target="_blank" rel="noopener noreferrer" class="text-primary">
                                                    <?php echo htmlspecialchars(strlen($value) > 30 ? substr($value, 0, 30) . '...' : $value); ?>
                                                </a>
                                                <button class="copy-btn" onclick="copyToClipboard('<?php echo htmlspecialchars(addslashes($value)); ?>', this)" title="複製連結">
                                                    <i class="bi bi-clipboard"></i>
                                                </button>
                                            <?php else: ?>
                                                <span><?php echo htmlspecialchars($value); ?></span>
                                                <?php if ($header === '推薦碼' || $header === '推薦碼/連結'): ?>
                                                <button class="copy-btn" onclick="copyToClipboard('<?php echo htmlspecialchars(addslashes($value)); ?>', this)" title="複製代碼">
                                                    <i class="bi bi-clipboard"></i>
                                                </button>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <?php endforeach; ?>
                                    <td class="action-column" style="display: none;">
                                        <button class="btn btn-sm btn-outline-primary me-2" onclick="showEditForm(<?php echo $index; ?>)">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteRow(<?php echo $index; ?>)">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mt-4">
                    <h2 class="mb-3">如何使用此網站</h2>
                    <div class="mb-3">
                        <h3>1. 尋找優惠/折扣</h3>
                        <p>搜尋你感興趣的服務/產品/網站</p>
                    </div>
                    <div class="mb-3">
                        <h3>2. 複製優惠/折扣</h3>
                        <p>點擊優惠碼旁的按鈕即可立即複製</p>
                    </div>
                    <div class="mb-3">
                        <h3>3. 貼上並使用</h3>
                        <p>在註冊/購買時記得貼上優惠折扣碼或點擊推薦連結以獲得你的折扣</p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 全域變數
        let allData = <?php echo isset($result['data']) ? json_encode($result['data']) : '[]'; ?>;
        let headers = <?php echo isset($result['headers']) ? json_encode($result['headers']) : '[]'; ?>;
        let editMode = false;
        
        // 當頁面載入完成時執行
        document.addEventListener('DOMContentLoaded', function() {
            // 設置搜尋功能
            document.getElementById('searchInput').addEventListener('input', filterData);
            
            <?php if (!empty($apiKey)): ?>
            // 設置編輯模式切換
            document.getElementById('toggleEditMode').addEventListener('click', toggleEditMode);
            
            // 設置新增資料按鈕
            document.getElementById('addNewRow').addEventListener('click', showAddForm);
            
            // 設置表單提交
            document.getElementById('dataForm').addEventListener('submit', saveData);
            
            // 設置取消編輯按鈕
            document.getElementById('cancelEdit').addEventListener('click', hideForm);
            <?php endif; ?>
        });
        
        // 複製到剪貼簿
        function copyToClipboard(text, button) {
            navigator.clipboard.writeText(text)
                .then(() => {
                    // 變更按鈕圖示為成功
                    const originalHTML = button.innerHTML;
                    button.innerHTML = '<i class="bi bi-check-lg"></i>';
                    button.classList.add('copy-success');
                    
                    // 2秒後恢復原始圖示
                    setTimeout(() => {
                        button.innerHTML = originalHTML;
                        button.classList.remove('copy-success');
                    }, 2000);
                })
                .catch(err => {
                    console.error('複製失敗：', err);
                    alert('複製到剪貼簿失敗');
                });
        }
        
        // 過濾資料
        function filterData() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const tableBody = document.getElementById('tableBody');
            const rows = tableBody.getElementsByTagName('tr');
            
            for (let i = 0; i < rows.length; i++) {
                const row = rows[i];
                const cells = row.getElementsByTagName('td');
                let found = false;
                
                for (let j = 0; j < cells.length - 1; j++) { // 排除操作列
                    const cellText = cells[j].textContent.toLowerCase();
                    if (cellText.includes(searchTerm)) {
                        found = true;
                        break;
                    }
                }
                
                row.style.display = found || !searchTerm ? '' : 'none';
            }
        }
        
        // 切換編輯模式
        function toggleEditMode() {
            editMode = !editMode;
            const toggleBtn = document.getElementById('toggleEditMode');
            const addNewBtn = document.getElementById('addNewRow');
            const actionColumns = document.querySelectorAll('.action-column');
            
            if (editMode) {
                toggleBtn.textContent = '退出編輯模式';
                toggleBtn.classList.replace('btn-primary', 'btn-warning');
                addNewBtn.style.display = 'inline-block';
                actionColumns.forEach(col => col.style.display = '');
            } else {
                toggleBtn.textContent = '切換編輯模式';
                toggleBtn.classList.replace('btn-warning', 'btn-primary');
                addNewBtn.style.display = 'none';
                actionColumns.forEach(col => col.style.display = 'none');
                hideForm();
            }
        }
        
        // 顯示編輯表單
        function showEditForm(rowIndex) {
            const form = document.getElementById('editForm');
            const formTitle = document.getElementById('formTitle');
            const formFields = document.getElementById('formFields');
            const rowIndexInput = document.getElementById('rowIndex');
            
            formTitle.textContent = '編輯資料';
            formFields.innerHTML = '';
            rowIndexInput.value = rowIndex;
            
            // 為每個欄位創建輸入框
            headers.forEach(header => {
                const div = document.createElement('div');
                div.className = 'mb-3';
                
                const label = document.createElement('label');
                label.htmlFor = `field_${header}`;
                label.className = 'form-label';
                label.textContent = header;
                
                const input = document.createElement('input');
                input.type = 'text';
                input.className = 'form-control';
                input.id = `field_${header}`;
                input.name = header;
                input.value = allData[rowIndex][header] || '';
                
                div.appendChild(label);
                div.appendChild(input);
                formFields.appendChild(div);
            });
            
            form.style.display = 'block';
        }
        
        // 顯示新增表單
        function showAddForm() {
            const form = document.getElementById('editForm');
            const formTitle = document.getElementById('formTitle');
            const formFields = document.getElementById('formFields');
            const rowIndexInput = document.getElementById('rowIndex');
            
            formTitle.textContent = '新增資料';
            formFields.innerHTML = '';
            rowIndexInput.value = 'new';
            
            // 為每個欄位創建輸入框
            headers.forEach(header => {
                const div = document.createElement('div');
                div.className = 'mb-3';
                
                const label = document.createElement('label');
                label.htmlFor = `field_${header}`;
                label.className = 'form-label';
                label.textContent = header;
                
                const input = document.createElement('input');
                input.type = 'text';
                input.className = 'form-control';
                input.id = `field_${header}`;
                input.name = header;
                input.value = '';
                
                div.appendChild(label);
                div.appendChild(input);
                formFields.appendChild(div);
            });
            
            form.style.display = 'block';
        }
        
        // 隱藏表單
        function hideForm() {
            document.getElementById('editForm').style.display = 'none';
        }
        
        // 儲存資料
        function saveData(event) {
            event.preventDefault();
            
            const rowIndex = document.getElementById('rowIndex').value;
            const isNewRow = rowIndex === 'new';
            const formData = {};
            
            // 收集表單資料
            headers.forEach(header => {
                formData[header] = document.getElementById(`field_${header}`).value || '';
            });
            
            // 更新本地資料
            if (isNewRow) {
                allData.push(formData);
                
                // 新增一行到表格
                const tableBody = document.getElementById('tableBody');
                const newRow = document.createElement('tr');
                
                headers.forEach(header => {
                    const td = document.createElement('td');
                    const div = document.createElement('div');
                    div.className = 'd-flex align-items-center gap-2';
                    
                    const span = document.createElement('span');
                    span.textContent = formData[header];
                    div.appendChild(span);
                    
                    if (header === '推薦碼' || header === '推薦碼/連結') {
                        const copyBtn = document.createElement('button');
                        copyBtn.className = 'copy-btn';
                        copyBtn.innerHTML = '<i class="bi bi-clipboard"></i>';
                        copyBtn.title = '複製代碼';
                        copyBtn.onclick = function() { copyToClipboard(formData[header], this); };
                        div.appendChild(copyBtn);
                    }
                    
                    td.appendChild(div);
                    newRow.appendChild(td);
                });
                
                // 添加操作列
                const actionTd = document.createElement('td');
                actionTd.className = 'action-column';
                if (!editMode) actionTd.style.display = 'none';
                
                const editBtn = document.createElement('button');
                editBtn.className = 'btn btn-sm btn-outline-primary me-2';
                editBtn.innerHTML = '<i class="bi bi-pencil"></i>';
                editBtn.onclick = function() { showEditForm(allData.length - 1); };
                
                const deleteBtn = document.createElement('button');
                deleteBtn.className = 'btn btn-sm btn-outline-danger';
                deleteBtn.innerHTML = '<i class="bi bi-trash"></i>';
                deleteBtn.onclick = function() { deleteRow(allData.length - 1); };
                
                actionTd.appendChild(editBtn);
                actionTd.appendChild(deleteBtn);
                newRow.appendChild(actionTd);
                
                tableBody.appendChild(newRow);
            } else {
                allData[rowIndex] = formData;
                
                // 更新表格中的行
                const tableBody = document.getElementById('tableBody');
                const rows = tableBody.getElementsByTagName('tr');
                const cells = rows[rowIndex].getElementsByTagName('td');
                
                headers.forEach((header, index) => {
                    const cell = cells[index];
                    const div = cell.querySelector('div');
                    const span = div.querySelector('span');
                    if (span) span.textContent = formData[header];
                });
            }
            
            // 隱藏表單
            hideForm();
            
            // 顯示成功訊息
            alert(isNewRow ? '新增資料成功！' : '更新資料成功！');
            
            // 注意：這個簡單版本不會實際更新 Google Sheets
            alert('注意：此簡單版本僅更新了頁面上的資料，並未實際儲存到 Google Sheets。若要永久儲存變更，請實作 Google Sheets API 寫入功能。');
        }
        
        // 刪除資料
        function deleteRow(rowIndex) {
            if (confirm('確定要刪除這筆資料嗎？')) {
                // 從本地資料中刪除
                allData.splice(rowIndex, 1);
                
                // 從表格中刪除行
                const tableBody = document.getElementById('tableBody');
                const rows = tableBody.getElementsByTagName('tr');
                rows[rowIndex].remove();
                
                // 更新剩餘行的事件處理器
                const actionButtons = document.querySelectorAll('.action-column button');
                for (let i = 0; i < actionButtons.length; i++) {
                    const button = actionButtons[i];
                    if (button.onclick.toString().includes('showEditForm')) {
                        const index = Math.floor(i / 2);
                        button.onclick = function() { showEditForm(index); };
                    } else if (button.onclick.toString().includes('deleteRow')) {
                        const index = Math.floor(i / 2);
                        button.onclick = function() { deleteRow(index); };
                    }
                }
                
                // 顯示成功訊息
                alert('刪除資料成功！');
                
                // 注意：這個簡單版本不會實際更新 Google Sheets
                alert('注意：此簡單版本僅更新了頁面上的資料，並未實際從 Google Sheets 刪除。若要永久儲存變更，請實作 Google Sheets API 寫入功能。');
            }
        }
    </script>
</body>
</html>
