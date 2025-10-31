<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Create base upload directory
    $baseUploadDir = 'uploads/';
    if (!file_exists($baseUploadDir)) {
        mkdir($baseUploadDir, 0777, true);
    }

    // Create year/month based directory
    $currentDate = new DateTime();
    $yearMonth = $currentDate->format('Y/m');
    $uploadDir = $baseUploadDir . $yearMonth . '/';
    
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Set proper PHP configurations for file uploads
    ini_set('upload_max_filesize', '10M');
    ini_set('post_max_size', '10M');
    
    // Define allowed MIME types
    $allowedTypes = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/gif'
    ];

    foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
        if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
            $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($fileInfo, $tmp_name);
            finfo_close($fileInfo);

            if (!in_array($mimeType, $allowedTypes)) {
                $errors[] = "File type not allowed. Only JPG, PNG, and GIF are accepted.";
                continue;
            }

            $file = $_FILES['images']['name'][$key];
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            $newFileName = uniqid() . '.' . $ext;
            $targetFile = $uploadDir . $newFileName;
            
            if (move_uploaded_file($tmp_name, $targetFile)) {
                $urlPath = 'http://' . $_SERVER['HTTP_HOST'] . '/image-url/' . $uploadDir . $newFileName;
                
                $stmt = $pdo->prepare("INSERT INTO images (user_id, filename, original_name, url_path, directory) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$_SESSION['user_id'], $newFileName, $file, $urlPath, $yearMonth]);
            }
        }
    }
    if (!isset($errors)) {
        // Redirect after successful upload
        header('Location: ' . $_SERVER['PHP_SELF'] . '?uploaded=true');
        exit;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Upload Images</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background-color: #f4f5f7;
            margin: 0;
            padding: 25px;
            color: #2d3436;
        }
        h1 {
            font-size: 24px;
            color: #2d3436;
            text-align: center;
            margin-bottom: 25px;
            font-weight: 500;
        }
        h2 {
            font-size: 20px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        .user-nav {
            background: white;
            padding: 12px;
            border-radius: 4px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
            margin-bottom: 25px;
            text-align: center;
        }
        .nav-link {
            display: inline-block;
            padding: 8px 16px;
            margin: 0 6px;
            border-radius: 4px;
            text-decoration: none;
            color: #2d3436;
            background: #f1f2f6;
        }
        .admin-link {
            background: #2d3436;
            color: white;
        }
        .upload-form {
            background: white;
            padding: 25px;
            border-radius: 4px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
            max-width: 800px;
            margin: 0 auto 25px;
        }
        .custom-file-upload {
            border: 1px solid #dfe6e9;
            display: inline-block;
            padding: 25px;
            cursor: pointer;
            border-radius: 4px;
            margin-bottom: 20px;
            color: #636e72;
            width: 70%;
            max-width: 400px;
        }
        .upload-btn, .bulk-actions button {
            background: #2d3436;
            color: white;
            padding: 8px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        .images-section {
            background: white;
            padding: 25px;
            border-radius: 4px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
            max-width: 1200px;
            margin: 0 auto;
        }
        .image-container {
            background: #f8f9fa;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
            border: 1px solid #dfe6e9;
        }
        .url-container {
            padding: 10px;
            margin-top: 15px;
            border-top: 1px solid #dfe6e9;
        }
        .url-input {
            padding: 8px;
            border: 1px solid #dfe6e9;
            border-radius: 4px;
            width: 60%;
            margin-right: 8px;
            font-size: 14px;
        }
        .success, .error {
            padding: 12px 16px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .success {
            background-color: #e3f2e6;
            color: #2b573d;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .delete-btn {
            background: #e74c3c;
        }
        .export-btn {
            background: #27ae60;
        }
        button:hover {
            opacity: 0.9;
        }
        img {
            border-radius: 4px;
            border: 1px solid #dfe6e9;
        }
    </style>
</head>
<body>
    <h1>Image Upload System</h1>
    
    <div class="user-nav">
        <?php if ($_SESSION['user_id'] == 1): ?>
            <a href="admin_approval.php" class="nav-link admin-link">Admin Panel</a>
        <?php endif; ?>
        <a href="change_password.php" class="nav-link">Change Password</a>
        <a href="logout.php" class="nav-link">Logout</a>
    </div>

    <?php if (isset($errors) && !empty($errors)): ?>
        <div class="error">
            <?php foreach($errors as $error): ?>
                <p><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="upload-form">
        <?php if (isset($_GET['uploaded']) && $_GET['uploaded'] === 'true'): ?>
            <div class="success">Images uploaded successfully!</div>
        <?php endif; ?>
        <form method="POST" enctype="multipart/form-data">
            <label for="file-upload" class="custom-file-upload">
                <i class="fas fa-cloud-upload-alt"></i>
                Choose Images to Upload
            </label>
            <input id="file-upload" type="file" name="images[]" multiple 
                   accept=".jpg,.jpeg,.png,.gif,image/jpeg,image/png,image/gif" required>
            <br>
            <button type="submit" class="upload-btn">Upload Images</button>
        </form>
    </div>
    <div class="images-section">
        <h2>Your Uploaded Images</h2>
        <div class="bulk-actions">
            <button onclick="toggleSelectMode()" id="selectModeBtn">Select Multiple</button>
            <button onclick="deleteSelected()" id="deleteSelectedBtn" style="display:none" class="delete-btn">Delete Selected</button>
            <button onclick="exportSelected()" id="exportSelectedBtn" style="display:none" class="export-btn">Export Selected</button>
            <button onclick="selectAll()" id="selectAllBtn" style="display:none">Select All</button>
        </div>
        <?php
        try {
            $stmt = $pdo->prepare("SELECT * FROM images WHERE user_id = ? ORDER BY upload_date DESC");
            $stmt->execute([$_SESSION['user_id']]);
            
            $serialNumber = 1;
            while ($image = $stmt->fetch()) {
                echo "<div class='image-container' id='image-" . $image['id'] . "'>";
                echo "<div class='serial-number'>#{$serialNumber}</div>";
                echo "<input type='checkbox' class='image-checkbox' data-id='" . $image['id'] . "' style='display:none;'>";
                echo "<img src='" . htmlspecialchars($image['url_path']) . "' width='200'><br>";
                echo "<div class='url-container'>";
                echo "URL: <input type='text' class='url-input' id='url-" . $image['id'] . "' value='" . htmlspecialchars($image['url_path']) . "' readonly>";
                echo "<button onclick='copyUrl(" . $image['id'] . ")'>Copy URL</button>";
                echo "<button onclick='deleteImage(" . $image['id'] . ")' class='delete-btn single-delete'>Delete</button>";
                echo "</div>";
                echo "</div>";
                $serialNumber++;
            }
        } catch (PDOException $e) {
            echo "<div class='error'>Error loading images</div>";
        }
        ?>
    </div>

    <script>
    // Add this new code at the start of your script section
    document.getElementById('file-upload').addEventListener('change', function(e) {
        const fileName = e.target.files.length > 1 
            ? e.target.files.length + ' files selected'
            : e.target.files[0].name;
        document.querySelector('.custom-file-upload').textContent = fileName;
    });

    function copyUrl(id) {
        var urlInput = document.getElementById('url-' + id);
        urlInput.select();
        document.execCommand('copy');
        alert('URL copied to clipboard!');
    }

    function deleteImage(id) {
        if (confirm('Are you sure you want to delete this image?')) {
            fetch('delete_image.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ id: id })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const imageElement = document.getElementById('image-' + id);
                    if (imageElement) {
                        imageElement.remove();
                        updateSerialNumbers();
                    }
                } else {
                    alert('Error deleting image: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                alert('Error deleting image');
                console.error('Error:', error);
            });
        }
    }

    function updateSerialNumbers() {
        const containers = document.querySelectorAll('.image-container');
        containers.forEach((container, index) => {
            const serialNumber = container.querySelector('.serial-number');
            if (serialNumber) {
                serialNumber.textContent = '#' + (index + 1);
            }
        });
    }

    let selectMode = false;

    function selectAll() {
        const checkboxes = document.querySelectorAll('.image-checkbox');
        const selectAllBtn = document.getElementById('selectAllBtn');
        const allChecked = Array.from(checkboxes).every(cb => cb.checked);

        checkboxes.forEach(cb => {
            cb.checked = !allChecked;
            cb.closest('.image-container').classList.toggle('selected', !allChecked);
        });

        selectAllBtn.textContent = allChecked ? 'Select All' : 'Deselect All';
    }

    function exportSelected() {
        const selectedIds = Array.from(document.querySelectorAll('.image-checkbox:checked'))
            .map(cb => cb.dataset.id);

        if (selectedIds.length === 0) {
            alert('Please select images to export');
            return;
        }

        window.location.href = 'export_images.php?ids=' + selectedIds.join(',');
    }

    function toggleSelectMode() {
        selectMode = !selectMode;
        const checkboxes = document.querySelectorAll('.image-checkbox');
        const deleteButtons = document.querySelectorAll('.single-delete');
        const selectModeBtn = document.getElementById('selectModeBtn');
        const deleteSelectedBtn = document.getElementById('deleteSelectedBtn');
        const exportSelectedBtn = document.getElementById('exportSelectedBtn');
        const selectAllBtn = document.getElementById('selectAllBtn');

        checkboxes.forEach(cb => {
            cb.style.display = selectMode ? 'block' : 'none';
            cb.checked = false;
        });

        deleteButtons.forEach(btn => {
            btn.style.display = selectMode ? 'none' : 'inline-block';
        });

        selectModeBtn.textContent = selectMode ? 'Cancel Selection' : 'Select Multiple';
        deleteSelectedBtn.style.display = selectMode ? 'inline-block' : 'none';
        exportSelectedBtn.style.display = selectMode ? 'inline-block' : 'none';
        selectAllBtn.style.display = selectMode ? 'inline-block' : 'none';

        document.querySelectorAll('.image-container').forEach(container => {
            container.classList.remove('selected');
        });
    }

    function deleteSelected() {
        const selectedIds = Array.from(document.querySelectorAll('.image-checkbox:checked'))
            .map(cb => cb.dataset.id);

        if (selectedIds.length === 0) {
            alert('Please select images to delete');
            return;
        }

        if (confirm('Are you sure you want to delete ' + selectedIds.length + ' selected images?')) {
            fetch('delete_image.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ ids: selectedIds })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    selectedIds.forEach(id => {
                        const imageElement = document.getElementById('image-' + id);
                        if (imageElement) {
                            imageElement.remove();
                        }
                    });
                    updateSerialNumbers();
                    toggleSelectMode();
                } else {
                    alert('Error deleting images: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                alert('Error deleting images');
                console.error('Error:', error);
            });
        }
    }

    document.addEventListener('click', function(e) {
        if (selectMode && e.target.closest('.image-container')) {
            const container = e.target.closest('.image-container');
            const checkbox = container.querySelector('.image-checkbox');
            if (e.target !== checkbox) {
                checkbox.checked = !checkbox.checked;
            }
            container.classList.toggle('selected', checkbox.checked);
        }
    });
    </script>
    <p><a href="logout.php">Logout</a></p>
</body>
</html>
