<?php
require_once 'config.php';
requireLogin();

// Handle task operations
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $user_id = $_SESSION['user_id'];
        
        if ($_POST['action'] == 'add') {
            $stmt = $conn->prepare("INSERT INTO tasks (user_id, date, project, task_description, priority, start_date, deadline, status, completion, comments) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssssssss", 
                $user_id,
                $_POST['date'],
                $_POST['project'],
                $_POST['task_description'],
                $_POST['priority'],
                $_POST['start_date'],
                $_POST['deadline'],
                $_POST['status'],
                $_POST['completion'],
                $_POST['comments']
            );
            $stmt->execute();
            $stmt->close();
        } elseif ($_POST['action'] == 'edit') {
            $stmt = $conn->prepare("UPDATE tasks SET date=?, project=?, task_description=?, priority=?, start_date=?, deadline=?, status=?, completion=?, comments=? WHERE id=? AND user_id=?");
            $stmt->bind_param("sssssssssii",
                $_POST['date'],
                $_POST['project'],
                $_POST['task_description'],
                $_POST['priority'],
                $_POST['start_date'],
                $_POST['deadline'],
                $_POST['status'],
                $_POST['completion'],
                $_POST['comments'],
                $_POST['task_id'],
                $user_id
            );
            $stmt->execute();
            $stmt->close();
        } elseif ($_POST['action'] == 'delete') {
            $stmt = $conn->prepare("DELETE FROM tasks WHERE id=? AND user_id=?");
            $stmt->bind_param("ii", $_POST['task_id'], $user_id);
            $stmt->execute();
            $stmt->close();
        } elseif ($_POST['action'] == 'quick_status') {
            $stmt = $conn->prepare("UPDATE tasks SET status=?, completion=? WHERE id=? AND user_id=?");
            $completion = $_POST['status'] == 'Completed' ? 100 : ($_POST['status'] == 'In Progress' ? 50 : 0);
            $stmt->bind_param("siii", $_POST['status'], $completion, $_POST['task_id'], $user_id);
            $stmt->execute();
            $stmt->close();
            echo json_encode(['success' => true]);
            exit();
        } elseif ($_POST['action'] == 'add_visit') {
            $stmt = $conn->prepare("INSERT INTO visits (user_id, visit_date, time_from, time_to, venue, reason) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssss", 
                $user_id,
                $_POST['visit_date'],
                $_POST['time_from'],
                $_POST['time_to'],
                $_POST['venue'],
                $_POST['reason']
            );
            $stmt->execute();
            $stmt->close();
        } elseif ($_POST['action'] == 'edit_visit') {
            $stmt = $conn->prepare("UPDATE visits SET visit_date=?, time_from=?, time_to=?, venue=?, reason=? WHERE id=? AND user_id=?");
            $stmt->bind_param("sssssii",
                $_POST['visit_date'],
                $_POST['time_from'],
                $_POST['time_to'],
                $_POST['venue'],
                $_POST['reason'],
                $_POST['visit_id'],
                $user_id
            );
            $stmt->execute();
            $stmt->close();
        } elseif ($_POST['action'] == 'delete_visit') {
            $stmt = $conn->prepare("DELETE FROM visits WHERE id=? AND user_id=?");
            $stmt->bind_param("ii", $_POST['visit_id'], $user_id);
            $stmt->execute();
            $stmt->close();
        }
        header("Location: index.php");
        exit();
    }
}

// Fetch all tasks
$user_id = $_SESSION['user_id'];
$result = $conn->query("SELECT * FROM tasks WHERE user_id=$user_id ORDER BY deadline ASC");

// Fetch all visits
$visits_result = $conn->query("SELECT * FROM visits WHERE user_id=$user_id ORDER BY visit_date DESC, time_from DESC");

// Get filter and sort from URL
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'deadline';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Tracker - Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Inter', sans-serif;
            background: #f5f7fa;
            color: #333;
        }
        .header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 50%, #7e8ba3 100%);
            color: white;
            padding: 0;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 40px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .header-brand {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .header-brand .icon {
            width: 45px;
            height: 45px;
            background: rgba(255,255,255,0.2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            backdrop-filter: blur(10px);
        }
        .header-brand h1 {
            font-size: 26px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }
        .header-brand .subtitle {
            font-size: 12px;
            opacity: 0.8;
            font-weight: 400;
        }
        .header-right {
            display: flex;
            align-items: center;
            gap: 25px;
        }
        .user-badge {
            display: flex;
            align-items: center;
            gap: 12px;
            background: rgba(255,255,255,0.15);
            padding: 10px 18px;
            border-radius: 25px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
        }
        .user-avatar {
            width: 35px;
            height: 35px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
        }
        .user-info {
            display: flex;
            flex-direction: column;
        }
        .user-info .name {
            font-weight: 600;
            font-size: 14px;
        }
        .user-info .role {
            font-size: 11px;
            opacity: 0.8;
        }
        .header-nav {
            display: flex;
            padding: 0 40px;
            gap: 5px;
        }
        .nav-item {
            padding: 15px 25px;
            background: transparent;
            border: none;
            color: rgba(255,255,255,0.8);
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s;
            border-bottom: 3px solid transparent;
            text-decoration: none;
            display: inline-block;
        }
        .nav-item:hover {
            color: white;
            background: rgba(255,255,255,0.1);
        }
        .nav-item.active {
            color: white;
            border-bottom-color: white;
            background: rgba(255,255,255,0.1);
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        .btn-logout {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 1px solid rgba(255,255,255,0.3);
            backdrop-filter: blur(10px);
        }
        .btn-logout:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-2px);
        }
        .container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 40px;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
        }
        .stat-card h3 {
            font-size: 14px;
            color: #666;
            margin-bottom: 8px;
        }
        .stat-card .number {
            font-size: 32px;
            font-weight: 700;
            color: #1e3c72;
        }
        .stat-card .trend {
            font-size: 12px;
            margin-top: 8px;
            color: #388e3c;
        }
        .section {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .section.hidden {
            display: none;
        }
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }
        .section-header h2 {
            font-size: 20px;
            font-weight: 600;
        }
        .header-controls {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }
        .search-box {
            position: relative;
        }
        .search-box input {
            padding: 10px 15px 10px 40px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            width: 250px;
            transition: border-color 0.3s;
        }
        .search-box input:focus {
            outline: none;
            border-color: #1e3c72;
        }
        .search-box::before {
            content: "🔍";
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
        }
        .date-filter {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .date-filter input {
            padding: 10px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
        }
        .sort-dropdown {
            padding: 10px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
            background: white;
            font-weight: 500;
        }
        .btn-add {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
        }
        .btn-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(30, 60, 114, 0.3);
        }
        
        /* Tabs Styling */
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
            overflow-x: auto;
        }
        .tab {
            padding: 12px 20px;
            background: transparent;
            border: none;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            color: #666;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
            white-space: nowrap;
        }
        .tab:hover {
            color: #1e3c72;
        }
        .tab.active {
            color: #1e3c72;
            border-bottom-color: #1e3c72;
        }
        .tab-badge {
            display: inline-block;
            background: #e0e0e0;
            color: #666;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 12px;
            margin-left: 6px;
        }
        .tab.active .tab-badge {
            background: #1e3c72;
            color: white;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
            color: #666;
            border-bottom: 2px solid #e0e0e0;
            cursor: pointer;
            user-select: none;
        }
        th:hover {
            background: #eef0f2;
        }
        td {
            padding: 16px 12px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
        }
        tr {
            transition: background 0.2s;
        }
        tr:hover {
            background: #f8f9ff;
        }
        .priority {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .priority-low { background: #e3f2fd; color: #1976d2; }
        .priority-medium { background: #fff3e0; color: #f57c00; }
        .priority-high { background: #ffebee; color: #d32f2f; }
        .priority-critical { background: #4a148c; color: white; }
        .status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .status:hover {
            opacity: 0.8;
            transform: scale(1.05);
        }
        .status-not-started { background: #fafafa; color: #666; }
        .status-in-progress { background: #e3f2fd; color: #1976d2; }
        .status-completed { background: #e8f5e9; color: #388e3c; }
        .status-on-hold { background: #fff3e0; color: #f57c00; }
        .progress-bar {
            width: 80px;
            height: 8px;
            background: #e0e0e0;
            border-radius: 4px;
            overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #1e3c72, #2a5298);
            transition: width 0.3s;
        }
        .actions {
            display: flex;
            gap: 8px;
        }
        .btn-small {
            padding: 6px 12px;
            font-size: 12px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s;
        }
        .btn-edit {
            background: #e3f2fd;
            color: #1976d2;
        }
        .btn-edit:hover {
            background: #1976d2;
            color: white;
        }
        .btn-delete {
            background: #ffebee;
            color: #d32f2f;
        }
        .btn-delete:hover {
            background: #d32f2f;
            color: white;
        }
        .btn-duplicate {
            background: #f3e5f5;
            color: #7b1fa2;
        }
        .btn-duplicate:hover {
            background: #7b1fa2;
            color: white;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal.active {
            display: flex;
        }
        .modal-content {
            background: white;
            border-radius: 16px;
            padding: 30px;
            width: 90%;
            max-width: 700px;
            max-height: 90vh;
            overflow-y: auto;
            animation: slideIn 0.3s ease-out;
        }
        @keyframes slideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .modal-header h2 {
            font-size: 22px;
            font-weight: 600;
        }
        .close-btn {
            background: none;
            border: none;
            font-size: 28px;
            cursor: pointer;
            color: #999;
        }
        .close-btn:hover {
            color: #333;
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .form-group {
            display: flex;
            flex-direction: column;
        }
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        label {
            margin-bottom: 8px;
            font-weight: 500;
            font-size: 14px;
            color: #333;
        }
        input, select, textarea {
            padding: 10px 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            transition: border-color 0.3s;
        }
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #1e3c72;
        }
        textarea {
            resize: vertical;
            min-height: 80px;
        }
        .form-actions {
            display: flex;
            gap: 12px;
            margin-top: 20px;
            justify-content: flex-end;
        }
        .btn-submit {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 12px 24px;
        }
        .btn-cancel {
            background: #f5f5f5;
            color: #666;
            padding: 12px 24px;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        .empty-state svg {
            width: 100px;
            height: 100px;
            margin-bottom: 20px;
            opacity: 0.3;
        }
        .deadline-indicator {
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .deadline-warning {
            color: #f57c00;
            font-weight: 600;
        }
        .deadline-danger {
            color: #d32f2f;
            font-weight: 600;
        }
        .quick-status-menu {
            display: none;
            position: absolute;
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            padding: 8px;
            z-index: 100;
            min-width: 150px;
        }
        .quick-status-menu.active {
            display: block;
        }
        .quick-status-option {
            padding: 8px 12px;
            cursor: pointer;
            border-radius: 6px;
            font-size: 13px;
            transition: background 0.2s;
        }
        .quick-status-option:hover {
            background: #f5f5f5;
        }
        .bulk-actions {
            display: none;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 15px;
            align-items: center;
            gap: 15px;
        }
        .bulk-actions.active {
            display: flex;
        }
        .checkbox-cell {
            width: 40px;
        }
        .task-checkbox {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        .progress-slider {
            width: 100%;
            cursor: pointer;
        }
        .view-toggle {
            display: flex;
            gap: 5px;
            background: #f0f0f0;
            padding: 4px;
            border-radius: 8px;
        }
        .view-btn {
            padding: 6px 12px;
            border: none;
            background: transparent;
            cursor: pointer;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            transition: background 0.2s;
        }
        .view-btn.active {
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .kanban-view {
            display: none;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .kanban-view.active {
            display: grid;
        }
        .kanban-column {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 15px;
        }
        .kanban-column h3 {
            font-size: 14px;
            margin-bottom: 15px;
            color: #666;
        }
        .kanban-card {
            background: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            cursor: pointer;
            transition: transform 0.2s;
        }
        .kanban-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .toast {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: #323232;
            color: white;
            padding: 16px 24px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            display: none;
            z-index: 2000;
            animation: slideUp 0.3s ease-out;
        }
        .toast.active {
            display: block;
        }
        @keyframes slideUp {
            from {
                transform: translateY(100px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        .visit-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 15px;
            border-left: 4px solid #1e3c72;
            transition: all 0.3s;
        }
        .visit-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transform: translateX(5px);
        }
        .visit-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }
        .visit-date-time {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .visit-date {
            font-weight: 600;
            font-size: 16px;
            color: #1e3c72;
        }
        .visit-time {
            font-size: 13px;
            color: #666;
        }
        .visit-venue {
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
        }
        .visit-reason {
            font-size: 14px;
            color: #666;
            line-height: 1.5;
        }
        .btn-export {
            background: linear-gradient(135deg, #43a047 0%, #66bb6a 100%);
            color: white;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .btn-export:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(67, 160, 71, 0.3);
        }
        .export-dropdown {
            position: relative;
            display: inline-block;
        }
        .export-menu {
            display: none;
            position: absolute;
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            padding: 8px;
            z-index: 100;
            min-width: 180px;
            right: 0;
            margin-top: 5px;
        }
        .export-menu.active {
            display: block;
        }
        .export-option {
            padding: 10px 15px;
            cursor: pointer;
            border-radius: 6px;
            font-size: 14px;
            transition: background 0.2s;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #333;
        }
        .export-option:hover {
            background: #f5f5f5;
        }
        .export-icon {
            font-size: 18px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-top">
            <div class="header-brand">
                <div class="icon">📋</div>
                <div>
                    <h1>Task Tracker Pro</h1>
                    <div class="subtitle">Productivity & Management Suite</div>
                </div>
            </div>
            <div class="header-right">
                <div class="user-badge">
                    <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?></div>
                    <div class="user-info">
                        <div class="name"><?php echo htmlspecialchars($_SESSION['username']); ?></div>
                        <div class="role">Administrator</div>
                    </div>
                </div>
                <a href="logout.php" class="btn btn-logout">Logout</a>
            </div>
        </div>
        <div class="header-nav">
            <button class="nav-item active" onclick="showSection('tasks')">Tasks</button>
            <button class="nav-item" onclick="showSection('visits')">Visits</button>
        </div>
    </div>

    <div class="container">
        <?php
        $total = $result->num_rows;
        $completed = $conn->query("SELECT COUNT(*) as count FROM tasks WHERE user_id=$user_id AND status='Completed'")->fetch_assoc()['count'];
        $in_progress = $conn->query("SELECT COUNT(*) as count FROM tasks WHERE user_id=$user_id AND status='In Progress'")->fetch_assoc()['count'];
        $not_started = $conn->query("SELECT COUNT(*) as count FROM tasks WHERE user_id=$user_id AND status='Not Started'")->fetch_assoc()['count'];
        $on_hold = $conn->query("SELECT COUNT(*) as count FROM tasks WHERE user_id=$user_id AND status='On Hold'")->fetch_assoc()['count'];
        $overdue = $conn->query("SELECT COUNT(*) as count FROM tasks WHERE user_id=$user_id AND deadline < CURDATE() AND status != 'Completed'")->fetch_assoc()['count'];
        $completion_rate = $total > 0 ? round(($completed / $total) * 100) : 0;
        
        $total_visits = $visits_result->num_rows;
        $today_visits = $conn->query("SELECT COUNT(*) as count FROM visits WHERE user_id=$user_id AND visit_date = CURDATE()")->fetch_assoc()['count'];
        $this_week_visits = $conn->query("SELECT COUNT(*) as count FROM visits WHERE user_id=$user_id AND YEARWEEK(visit_date) = YEARWEEK(CURDATE())")->fetch_assoc()['count'];
        ?>
        
        <!-- Tasks Section -->
        <div id="tasks-section" class="section-container">
            <div class="stats">
                <div class="stat-card" onclick="switchTab('all')">
                    <h3>Total Tasks</h3>
                    <div class="number"><?php echo $total; ?></div>
                    <div class="trend">📊 All tasks</div>
                </div>
                <div class="stat-card" onclick="switchTab('completed')">
                    <h3>Completed</h3>
                    <div class="number"><?php echo $completed; ?></div>
                    <div class="trend">✅ <?php echo $completion_rate; ?>% completion rate</div>
                </div>
                <div class="stat-card" onclick="switchTab('in-progress')">
                    <h3>In Progress</h3>
                    <div class="number"><?php echo $in_progress; ?></div>
                    <div class="trend">🔄 Active tasks</div>
                </div>
                <div class="stat-card" onclick="switchTab('overdue')">
                    <h3>Overdue</h3>
                    <div class="number" style="color: #d32f2f;"><?php echo $overdue; ?></div>
                    <div class="trend" style="color: #d32f2f;">⚠️ Needs attention</div>
                </div>
            </div>

            <div class="section">
                <div class="section-header">
                    <h2>Tasks</h2>
                    <div class="header-controls">
                        <div class="search-box">
                            <input type="text" id="searchInput" placeholder="Search tasks..." onkeyup="searchTasks()">
                        </div>
                        <select class="sort-dropdown" id="sortSelect" onchange="sortTasks()">
                            <option value="deadline">Sort by Deadline</option>
                            <option value="priority">Sort by Priority</option>
                            <option value="project">Sort by Project</option>
                            <option value="status">Sort by Status</option>
                        </select>
                        <div class="view-toggle">
                            <button class="view-btn active" onclick="switchView('table')">📋 Table</button>
                            <button class="view-btn" onclick="switchView('kanban')">📊 Kanban</button>
                        </div>
                        <div class="export-dropdown">
                            <button class="btn btn-export" onclick="toggleExportMenu('task')">
                                📥 Export
                            </button>
                            <div class="export-menu" id="taskExportMenu">
                                <div class="export-option" onclick="exportTasks('pdf')">
                                    <span class="export-icon">📄</span>
                                    <span>Export as PDF</span>
                                </div>
                                <div class="export-option" onclick="exportTasks('excel')">
                                    <span class="export-icon">📊</span>
                                    <span>Export as Excel</span>
                                </div>
                            </div>
                        </div>
                        <button class="btn btn-add" onclick="openModal()">+ Add New Task</button>
                    </div>
                </div>
                
                <!-- Tabs -->
                <div class="tabs">
                    <button class="tab active" onclick="switchTab('all')">
                        All Tasks <span class="tab-badge"><?php echo $total; ?></span>
                    </button>
                    <button class="tab" onclick="switchTab('not-started')">
                        Not Started <span class="tab-badge"><?php echo $not_started; ?></span>
                    </button>
                    <button class="tab" onclick="switchTab('in-progress')">
                        In Progress <span class="tab-badge"><?php echo $in_progress; ?></span>
                    </button>
                    <button class="tab" onclick="switchTab('completed')">
                        Completed <span class="tab-badge"><?php echo $completed; ?></span>
                    </button>
                    <button class="tab" onclick="switchTab('on-hold')">
                        On Hold <span class="tab-badge"><?php echo $on_hold; ?></span>
                    </button>
                    <button class="tab" onclick="switchTab('overdue')">
                        Overdue <span class="tab-badge" style="background: #ffebee; color: #d32f2f;"><?php echo $overdue; ?></span>
                    </button>
                </div>
                
                <?php
                // Reset result pointer
                $result->data_seek(0);
                
                // Organize tasks by category
                $tasks = [
                    'all' => [],
                    'not-started' => [],
                    'in-progress' => [],
                    'completed' => [],
                    'on-hold' => [],
                    'overdue' => []
                ];
                
                while ($task = $result->fetch_assoc()) {
                    $tasks['all'][] = $task;
                    
                    if ($task['status'] == 'Not Started') {
                        $tasks['not-started'][] = $task;
                    }
                    if ($task['status'] == 'In Progress') {
                        $tasks['in-progress'][] = $task;
                    }
                    if ($task['status'] == 'Completed') {
                        $tasks['completed'][] = $task;
                    }
                    if ($task['status'] == 'On Hold') {
                        $tasks['on-hold'][] = $task;
                    }
                    if (strtotime($task['deadline']) < strtotime(date('Y-m-d')) && $task['status'] != 'Completed') {
                        $tasks['overdue'][] = $task;
                    }
                }
                ?>
                
                <!-- Bulk Actions -->
                <div class="bulk-actions" id="bulkActions">
                    <span id="selectedCount">0 selected</span>
                    <button class="btn-small btn-edit" onclick="bulkUpdateStatus()">Change Status</button>
                    <button class="btn-small btn-delete" onclick="bulkDelete()">Delete Selected</button>
                    <button class="btn-small" style="background: #f5f5f5;" onclick="clearSelection()">Clear</button>
                </div>
                
                <!-- Table View -->
                <div id="tableView">
                    <?php foreach ($tasks as $category => $categoryTasks): ?>
                    <div class="tab-content <?php echo $category == 'all' ? 'active' : ''; ?>" id="<?php echo $category; ?>-content">
                        <?php if (count($categoryTasks) > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th class="checkbox-cell">
                                        <input type="checkbox" class="task-checkbox" onchange="toggleAllCheckboxes(this, '<?php echo $category; ?>')">
                                    </th>
                                    <th onclick="sortTableBy('date', '<?php echo $category; ?>')">Date ⇅</th>
                                    <th onclick="sortTableBy('project', '<?php echo $category; ?>')">Project ⇅</th>
                                    <th>Task</th>
                                    <th onclick="sortTableBy('priority', '<?php echo $category; ?>')">Priority ⇅</th>
                                    <th onclick="sortTableBy('deadline', '<?php echo $category; ?>')">Deadline ⇅</th>
                                    <th>Status</th>
                                    <th>Progress</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody class="task-tbody" data-category="<?php echo $category; ?>">
                                <?php foreach ($categoryTasks as $task): 
                                    $daysUntilDeadline = (strtotime($task['deadline']) - strtotime(date('Y-m-d'))) / (60 * 60 * 24);
                                    $deadlineClass = '';
                                    if ($daysUntilDeadline < 0 && $task['status'] != 'Completed') {
                                        $deadlineClass = 'deadline-danger';
                                    } elseif ($daysUntilDeadline <= 3 && $task['status'] != 'Completed') {
                                        $deadlineClass = 'deadline-warning';
                                    }
                                ?>
                                <tr data-task-id="<?php echo $task['id']; ?>">
                                    <td class="checkbox-cell">
                                        <input type="checkbox" class="task-checkbox" value="<?php echo $task['id']; ?>" onchange="updateBulkActions()">
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($task['date'])); ?></td>
                                    <td><strong><?php echo htmlspecialchars($task['project']); ?></strong></td>
                                    <td><?php echo htmlspecialchars(substr($task['task_description'], 0, 50)) . (strlen($task['task_description']) > 50 ? '...' : ''); ?></td>
                                    <td>
                                        <span class="priority priority-<?php echo strtolower(str_replace(' ', '-', $task['priority'])); ?>">
                                            <?php echo $task['priority']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="deadline-indicator <?php echo $deadlineClass; ?>">
                                            <?php echo date('M d, Y', strtotime($task['deadline'])); ?>
                                            <?php if ($daysUntilDeadline < 0 && $task['status'] != 'Completed'): ?>
                                                🔴
                                            <?php elseif ($daysUntilDeadline <= 3 && $task['status'] != 'Completed'): ?>
                                                ⚠️
                                            <?php endif; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status status-<?php echo strtolower(str_replace(' ', '-', $task['status'])); ?>" 
                                              onclick="showQuickStatusMenu(event, <?php echo $task['id']; ?>, '<?php echo $task['status']; ?>')">
                                            <?php echo $task['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <div class="progress-bar">
                                                <div class="progress-fill" style="width: <?php echo $task['completion']; ?>%"></div>
                                            </div>
                                            <span style="font-size: 12px; color: #666;"><?php echo $task['completion']; ?>%</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="actions">
                                            <button class="btn-small btn-edit" onclick='editTask(<?php echo json_encode($task); ?>)'>Edit</button>
                                            <button class="btn-small btn-duplicate" onclick='duplicateTask(<?php echo json_encode($task); ?>)'>Duplicate</button>
                                            <button class="btn-small btn-delete" onclick="deleteTask(<?php echo $task['id']; ?>)">Delete</button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                        <div class="empty-state">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                            <h3>No tasks in this category</h3>
                            <p>Tasks will appear here when they match this status.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Kanban View -->
                <div id="kanbanView" class="kanban-view">
                    <div class="kanban-column">
                        <h3>📝 Not Started (<?php echo $not_started; ?>)</h3>
                        <?php foreach ($tasks['not-started'] as $task): ?>
                        <div class="kanban-card" onclick='editTask(<?php echo json_encode($task); ?>)'>
                            <div style="margin-bottom: 8px;"><strong><?php echo htmlspecialchars($task['project']); ?></strong></div>
                            <div style="font-size: 13px; color: #666; margin-bottom: 8px;"><?php echo htmlspecialchars(substr($task['task_description'], 0, 60)) . '...'; ?></div>
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span class="priority priority-<?php echo strtolower(str_replace(' ', '-', $task['priority'])); ?>"><?php echo $task['priority']; ?></span>
                                <span style="font-size: 12px; color: #999;">⏰ <?php echo date('M d', strtotime($task['deadline'])); ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="kanban-column">
                        <h3>🔄 In Progress (<?php echo $in_progress; ?>)</h3>
                        <?php foreach ($tasks['in-progress'] as $task): ?>
                        <div class="kanban-card" onclick='editTask(<?php echo json_encode($task); ?>)'>
                            <div style="margin-bottom: 8px;"><strong><?php echo htmlspecialchars($task['project']); ?></strong></div>
                            <div style="font-size: 13px; color: #666; margin-bottom: 8px;"><?php echo htmlspecialchars(substr($task['task_description'], 0, 60)) . '...'; ?></div>
                            <div style="margin-bottom: 8px;">
                                <div class="progress-bar" style="width: 100%;">
                                    <div class="progress-fill" style="width: <?php echo $task['completion']; ?>%"></div>
                                </div>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span class="priority priority-<?php echo strtolower(str_replace(' ', '-', $task['priority'])); ?>"><?php echo $task['priority']; ?></span>
                                <span style="font-size: 12px; color: #999;">⏰ <?php echo date('M d', strtotime($task['deadline'])); ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="kanban-column">
                        <h3>⏸️ On Hold (<?php echo $on_hold; ?>)</h3>
                        <?php foreach ($tasks['on-hold'] as $task): ?>
                        <div class="kanban-card" onclick='editTask(<?php echo json_encode($task); ?>)'>
                            <div style="margin-bottom: 8px;"><strong><?php echo htmlspecialchars($task['project']); ?></strong></div>
                            <div style="font-size: 13px; color: #666; margin-bottom: 8px;"><?php echo htmlspecialchars(substr($task['task_description'], 0, 60)) . '...'; ?></div>
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span class="priority priority-<?php echo strtolower(str_replace(' ', '-', $task['priority'])); ?>"><?php echo $task['priority']; ?></span>
                                <span style="font-size: 12px; color: #999;">⏰ <?php echo date('M d', strtotime($task['deadline'])); ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="kanban-column">
                        <h3>✅ Completed (<?php echo $completed; ?>)</h3>
                        <?php foreach ($tasks['completed'] as $task): ?>
                        <div class="kanban-card" onclick='editTask(<?php echo json_encode($task); ?>)'>
                            <div style="margin-bottom: 8px;"><strong><?php echo htmlspecialchars($task['project']); ?></strong></div>
                            <div style="font-size: 13px; color: #666; margin-bottom: 8px;"><?php echo htmlspecialchars(substr($task['task_description'], 0, 60)) . '...'; ?></div>
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span class="priority priority-<?php echo strtolower(str_replace(' ', '-', $task['priority'])); ?>"><?php echo $task['priority']; ?></span>
                                <span style="font-size: 12px; color: #999;">✓ Done</span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Visits Section -->
        <div id="visits-section" class="section-container hidden">
            <div class="stats">
                <div class="stat-card">
                    <h3>Total Visits</h3>
                    <div class="number"><?php echo $total_visits; ?></div>
                    <div class="trend">📍 All time</div>
                </div>
                <div class="stat-card">
                    <h3>Today</h3>
                    <div class="number"><?php echo $today_visits; ?></div>
                    <div class="trend">📅 Today's visits</div>
                </div>
                <div class="stat-card">
                    <h3>This Week</h3>
                    <div class="number"><?php echo $this_week_visits; ?></div>
                    <div class="trend">📊 Current week</div>
                </div>
            </div>

            <div class="section">
                <div class="section-header">
                    <h2>Visit Tracker</h2>
                    <div class="header-controls">
                        <div class="search-box">
                            <input type="text" id="visitSearchInput" placeholder="Search visits..." onkeyup="searchVisits()">
                        </div>
                        <div class="date-filter">
                            <input type="date" id="visitDateFrom" placeholder="From">
                            <span>to</span>
                            <input type="date" id="visitDateTo" placeholder="To">
                            <button class="btn btn-add" onclick="filterVisitsByDate()">Filter</button>
                            <button class="btn btn-cancel" onclick="clearVisitFilter()">Clear</button>
                        </div>
                        <div class="export-dropdown">
                            <button class="btn btn-export" onclick="toggleExportMenu('visit')">
                                📥 Export
                            </button>
                            <div class="export-menu" id="visitExportMenu">
                                <div class="export-option" onclick="exportVisits('pdf')">
                                    <span class="export-icon">📄</span>
                                    <span>Export as PDF</span>
                                </div>
                                <div class="export-option" onclick="exportVisits('excel')">
                                    <span class="export-icon">📊</span>
                                    <span>Export as Excel</span>
                                </div>
                            </div>
                        </div>
                        <button class="btn btn-add" onclick="openVisitModal()">+ Add Visit</button>
                    </div>
                </div>
                
                <div id="visitsContainer">
                    <?php 
                    if ($total_visits > 0):
                        while ($visit = $visits_result->fetch_assoc()): 
                    ?>
                    <div class="visit-card" data-visit-date="<?php echo $visit['visit_date']; ?>">
                        <div class="visit-header">
                            <div class="visit-date-time">
                                <div class="visit-date">📅 <?php echo date('l, F d, Y', strtotime($visit['visit_date'])); ?></div>
                                <div class="visit-time">🕒 <?php echo date('g:i A', strtotime($visit['time_from'])); ?> - <?php echo date('g:i A', strtotime($visit['time_to'])); ?></div>
                            </div>
                            <div class="actions">
                                <button class="btn-small btn-edit" onclick='editVisit(<?php echo json_encode($visit); ?>)'>Edit</button>
                                <button class="btn-small btn-delete" onclick="deleteVisit(<?php echo $visit['id']; ?>)">Delete</button>
                            </div>
                        </div>
                        <div class="visit-venue">📍 <?php echo htmlspecialchars($visit['venue']); ?></div>
                        <div class="visit-reason"><?php echo htmlspecialchars($visit['reason']); ?></div>
                    </div>
                    <?php 
                        endwhile;
                    else: 
                    ?>
                    <div class="empty-state">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        <h3>No visits recorded yet</h3>
                        <p>Start tracking your visits by clicking the "Add Visit" button.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Status Change Menu -->
    <div class="quick-status-menu" id="quickStatusMenu">
        <div class="quick-status-option" onclick="quickChangeStatus('Not Started')">📝 Not Started</div>
        <div class="quick-status-option" onclick="quickChangeStatus('In Progress')">🔄 In Progress</div>
        <div class="quick-status-option" onclick="quickChangeStatus('On Hold')">⏸️ On Hold</div>
        <div class="quick-status-option" onclick="quickChangeStatus('Completed')">✅ Completed</div>
    </div>

    <!-- Add/Edit Task Modal -->
    <div class="modal" id="taskModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Add New Task</h2>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            
            <form method="POST" action="">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="task_id" id="taskId">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Date</label>
                        <input type="date" name="date" id="date" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Project</label>
                        <input type="text" name="project" id="project" required>
                    </div>
                    
                    <div class="form-group full-width">
                        <label>Task Description</label>
                        <textarea name="task_description" id="task_description" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Priority</label>
                        <select name="priority" id="priority" required>
                            <option value="Low">Low</option>
                            <option value="Medium" selected>Medium</option>
                            <option value="High">High</option>
                            <option value="Critical">Critical</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" id="status" required>
                            <option value="Not Started" selected>Not Started</option>
                            <option value="In Progress">In Progress</option>
                            <option value="Completed">Completed</option>
                            <option value="On Hold">On Hold</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Start Date</label>
                        <input type="date" name="start_date" id="start_date" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Deadline</label>
                        <input type="date" name="deadline" id="deadline" required>
                    </div>
                    
                    <div class="form-group full-width">
                        <label>Completion (%): <span id="completionValue">0</span>%</label>
                        <input type="range" name="completion" id="completion" class="progress-slider" min="0" max="100" value="0" oninput="updateCompletionValue(this.value)" required>
                    </div>
                    
                    <div class="form-group full-width">
                        <label>Comments</label>
                        <textarea name="comments" id="comments"></textarea>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-cancel" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-submit">Save Task</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add/Edit Visit Modal -->
    <div class="modal" id="visitModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="visitModalTitle">Add New Visit</h2>
                <button class="close-btn" onclick="closeVisitModal()">&times;</button>
            </div>
            
            <form method="POST" action="">
                <input type="hidden" name="action" id="visitFormAction" value="add_visit">
                <input type="hidden" name="visit_id" id="visitId">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Visit Date</label>
                        <input type="date" name="visit_date" id="visit_date" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Time</label>
                        <div class="time-inputs">
                            <input type="time" name="time_from" id="time_from" required>
                            <span class="time-separator">to</span>
                            <input type="time" name="time_to" id="time_to" required>
                        </div>
                    </div>
                    
                    <div class="form-group full-width">
                        <label>Venue</label>
                        <input type="text" name="venue" id="venue" required placeholder="Enter visit location">
                    </div>
                    
                    <div class="form-group full-width">
                        <label>Reason for Visit</label>
                        <textarea name="reason" id="reason" required placeholder="Describe the purpose of your visit"></textarea>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-cancel" onclick="closeVisitModal()">Cancel</button>
                    <button type="submit" class="btn btn-submit">Save Visit</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Toast Notification -->
    <div class="toast" id="toast"></div>

    <script>
        let currentTaskId = null;
        let currentView = 'table';
        
        // Section Management
        function showSection(section) {
            const sections = document.querySelectorAll('.section-container');
            sections.forEach(s => s.classList.add('hidden'));
            
            const navItems = document.querySelectorAll('.nav-item');
            navItems.forEach(item => item.classList.remove('active'));
            
            document.getElementById(section + '-section').classList.remove('hidden');
            event.target.classList.add('active');
        }
        
        // Task Functions
        function openModal() {
            document.getElementById('taskModal').classList.add('active');
            document.getElementById('modalTitle').textContent = 'Add New Task';
            document.getElementById('formAction').value = 'add';
            document.querySelector('#taskModal form').reset();
            document.getElementById('date').valueAsDate = new Date();
            document.getElementById('start_date').valueAsDate = new Date();
            updateCompletionValue(0);
        }
        
        function closeModal() {
            document.getElementById('taskModal').classList.remove('active');
        }
        
        function editTask(task) {
            document.getElementById('taskModal').classList.add('active');
            document.getElementById('modalTitle').textContent = 'Edit Task';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('taskId').value = task.id;
            document.getElementById('date').value = task.date;
            document.getElementById('project').value = task.project;
            document.getElementById('task_description').value = task.task_description;
            document.getElementById('priority').value = task.priority;
            document.getElementById('status').value = task.status;
            document.getElementById('start_date').value = task.start_date;
            document.getElementById('deadline').value = task.deadline;
            document.getElementById('completion').value = task.completion;
            updateCompletionValue(task.completion);
            document.getElementById('comments').value = task.comments || '';
        }
        
        function duplicateTask(task) {
            document.getElementById('taskModal').classList.add('active');
            document.getElementById('modalTitle').textContent = 'Duplicate Task';
            document.getElementById('formAction').value = 'add';
            document.getElementById('date').valueAsDate = new Date();
            document.getElementById('project').value = task.project;
            document.getElementById('task_description').value = task.task_description + ' (Copy)';
            document.getElementById('priority').value = task.priority;
            document.getElementById('status').value = 'Not Started';
            document.getElementById('start_date').valueAsDate = new Date();
            document.getElementById('deadline').value = task.deadline;
            document.getElementById('completion').value = 0;
            updateCompletionValue(0);
            document.getElementById('comments').value = task.comments || '';
            showToast('Task duplicated! Edit and save to create.');
        }
        
        function deleteTask(id) {
            if (confirm('Are you sure you want to delete this task?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="task_id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Visit Functions
        function openVisitModal() {
            document.getElementById('visitModal').classList.add('active');
            document.getElementById('visitModalTitle').textContent = 'Add New Visit';
            document.getElementById('visitFormAction').value = 'add_visit';
            document.querySelector('#visitModal form').reset();
            document.getElementById('visit_date').valueAsDate = new Date();
        }
        
        function closeVisitModal() {
            document.getElementById('visitModal').classList.remove('active');
        }
        
        function editVisit(visit) {
            document.getElementById('visitModal').classList.add('active');
            document.getElementById('visitModalTitle').textContent = 'Edit Visit';
            document.getElementById('visitFormAction').value = 'edit_visit';
            document.getElementById('visitId').value = visit.id;
            document.getElementById('visit_date').value = visit.visit_date;
            document.getElementById('time_from').value = visit.time_from;
            document.getElementById('time_to').value = visit.time_to;
            document.getElementById('venue').value = visit.venue;
            document.getElementById('reason').value = visit.reason;
        }
        
        function deleteVisit(id) {
            if (confirm('Are you sure you want to delete this visit?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_visit">
                    <input type="hidden" name="visit_id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function searchVisits() {
            const input = document.getElementById('visitSearchInput').value.toLowerCase();
            const visitCards = document.querySelectorAll('.visit-card');
            
            visitCards.forEach(card => {
                const text = card.textContent.toLowerCase();
                card.style.display = text.includes(input) ? '' : 'none';
            });
        }
        
        function filterVisitsByDate() {
            const dateFrom = document.getElementById('visitDateFrom').value;
            const dateTo = document.getElementById('visitDateTo').value;
            
            if (!dateFrom && !dateTo) {
                showToast('Please select at least one date');
                return;
            }
            
            const visitCards = document.querySelectorAll('.visit-card');
            let visibleCount = 0;
            
            visitCards.forEach(card => {
                const visitDate = card.getAttribute('data-visit-date');
                let show = true;
                
                if (dateFrom && visitDate < dateFrom) {
                    show = false;
                }
                if (dateTo && visitDate > dateTo) {
                    show = false;
                }
                
                card.style.display = show ? '' : 'none';
                if (show) visibleCount++;
            });
            
            showToast(`Showing ${visibleCount} visit(s)`);
        }
        
        function clearVisitFilter() {
            document.getElementById('visitDateFrom').value = '';
            document.getElementById('visitDateTo').value = '';
            document.getElementById('visitSearchInput').value = '';
            
            const visitCards = document.querySelectorAll('.visit-card');
            visitCards.forEach(card => {
                card.style.display = '';
            });
            
            showToast('Filter cleared');
        }
        
        function switchTab(tabName) {
            const tabs = document.querySelectorAll('.tab');
            tabs.forEach(tab => tab.classList.remove('active'));
            
            const tabContents = document.querySelectorAll('.tab-content');
            tabContents.forEach(content => content.classList.remove('active'));
            
            event.target.classList.add('active');
            document.getElementById(tabName + '-content').classList.add('active');
        }
        
        function searchTasks() {
            const input = document.getElementById('searchInput').value.toLowerCase();
            const activeTab = document.querySelector('.tab-content.active');
            const rows = activeTab.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(input) ? '' : 'none';
            });
        }
        
        function sortTasks() {
            const sortBy = document.getElementById('sortSelect').value;
            const activeTab = document.querySelector('.tab-content.active');
            const tbody = activeTab.querySelector('tbody');
            if (!tbody) return;
            
            const rows = Array.from(tbody.querySelectorAll('tr'));
            
            rows.sort((a, b) => {
                let aVal, bVal;
                
                switch(sortBy) {
                    case 'deadline':
                        aVal = new Date(a.children[5].textContent);
                        bVal = new Date(b.children[5].textContent);
                        break;
                    case 'priority':
                        const priorityOrder = {'Critical': 4, 'High': 3, 'Medium': 2, 'Low': 1};
                        aVal = priorityOrder[a.children[4].textContent.trim()] || 0;
                        bVal = priorityOrder[b.children[4].textContent.trim()] || 0;
                        return bVal - aVal;
                    case 'project':
                        aVal = a.children[2].textContent;
                        bVal = b.children[2].textContent;
                        break;
                    case 'status':
                        aVal = a.children[6].textContent;
                        bVal = b.children[6].textContent;
                        break;
                }
                
                return aVal > bVal ? 1 : -1;
            });
            
            rows.forEach(row => tbody.appendChild(row));
        }
        
        function sortTableBy(column, category) {
            sortTasks();
        }
        
        function showQuickStatusMenu(event, taskId, currentStatus) {
            event.stopPropagation();
            const menu = document.getElementById('quickStatusMenu');
            menu.style.left = event.pageX + 'px';
            menu.style.top = event.pageY + 'px';
            menu.classList.add('active');
            currentTaskId = taskId;
            
            document.addEventListener('click', hideQuickStatusMenu);
        }
        
        function hideQuickStatusMenu() {
            document.getElementById('quickStatusMenu').classList.remove('active');
            document.removeEventListener('click', hideQuickStatusMenu);
        }
        
        function quickChangeStatus(newStatus) {
            if (!currentTaskId) return;
            
            const formData = new FormData();
            formData.append('action', 'quick_status');
            formData.append('task_id', currentTaskId);
            formData.append('status', newStatus);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Status updated to: ' + newStatus);
                    setTimeout(() => location.reload(), 1000);
                }
            });
            
            hideQuickStatusMenu();
        }
        
        function toggleAllCheckboxes(masterCheckbox, category) {
            const checkboxes = document.querySelectorAll(`[data-category="${category}"] .task-checkbox`);
            checkboxes.forEach(cb => cb.checked = masterCheckbox.checked);
            updateBulkActions();
        }
        
        function updateBulkActions() {
            const checked = document.querySelectorAll('.task-checkbox:checked').length;
            const bulkActions = document.getElementById('bulkActions');
            const selectedCount = document.getElementById('selectedCount');
            
            if (checked > 0) {
                bulkActions.classList.add('active');
                selectedCount.textContent = checked + ' selected';
            } else {
                bulkActions.classList.remove('active');
            }
        }
        
        function clearSelection() {
            document.querySelectorAll('.task-checkbox').forEach(cb => cb.checked = false);
            updateBulkActions();
        }
        
        function bulkDelete() {
            const selected = Array.from(document.querySelectorAll('.task-checkbox:checked'))
                .map(cb => cb.value);
            
            if (selected.length === 0) return;
            
            if (confirm(`Delete ${selected.length} task(s)?`)) {
                selected.forEach(id => {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="task_id" value="${id}">
                    `;
                    document.body.appendChild(form);
                });
                location.reload();
            }
        }
        
        function bulkUpdateStatus() {
            const selected = Array.from(document.querySelectorAll('.task-checkbox:checked'))
                .map(cb => cb.value);
            
            if (selected.length === 0) return;
            
            const newStatus = prompt('Enter new status:\n1. Not Started\n2. In Progress\n3. Completed\n4. On Hold');
            const statusMap = {
                '1': 'Not Started',
                '2': 'In Progress',
                '3': 'Completed',
                '4': 'On Hold'
            };
            
            if (statusMap[newStatus]) {
                showToast(`Updating ${selected.length} task(s)...`);
            }
        }
        
        function switchView(view) {
            currentView = view;
            const tableView = document.getElementById('tableView');
            const kanbanView = document.getElementById('kanbanView');
            const viewBtns = document.querySelectorAll('.view-btn');
            
            viewBtns.forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            
            if (view === 'table') {
                tableView.style.display = 'block';
                kanbanView.classList.remove('active');
            } else {
                tableView.style.display = 'none';
                kanbanView.classList.add('active');
            }
        }
        
        function updateCompletionValue(value) {
            document.getElementById('completionValue').textContent = value;
        }
        
        function showToast(message) {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.classList.add('active');
            
            setTimeout(() => {
                toast.classList.remove('active');
            }, 3000);
        }
        
        // Export Functions
        function toggleExportMenu(type) {
            const menuId = type === 'task' ? 'taskExportMenu' : 'visitExportMenu';
            const menu = document.getElementById(menuId);
            
            // Close other menu
            document.querySelectorAll('.export-menu').forEach(m => {
                if (m.id !== menuId) m.classList.remove('active');
            });
            
            menu.classList.toggle('active');
            
            if (menu.classList.contains('active')) {
                document.addEventListener('click', function closeMenu(e) {
                    if (!e.target.closest('.export-dropdown')) {
                        menu.classList.remove('active');
                        document.removeEventListener('click', closeMenu);
                    }
                });
            }
        }
        
        function exportTasks(format) {
            const activeTab = document.querySelector('.tab.active');
            const tabName = activeTab ? activeTab.textContent.trim().split(' ')[0] : 'All';
            const searchQuery = document.getElementById('searchInput').value;
            
            showToast(`Exporting tasks as ${format.toUpperCase()}...`);
            
            // Get visible tasks
            const visibleRows = [];
            const activeContent = document.querySelector('.tab-content.active');
            const rows = activeContent.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                if (row.style.display !== 'none') {
                    const cells = row.querySelectorAll('td');
                    visibleRows.push({
                        date: cells[1].textContent.trim(),
                        project: cells[2].textContent.trim(),
                        task: cells[3].textContent.trim(),
                        priority: cells[4].textContent.trim(),
                        deadline: cells[5].textContent.trim(),
                        status: cells[6].textContent.trim(),
                        completion: cells[7].textContent.trim()
                    });
                }
            });
            
            if (visibleRows.length === 0) {
                showToast('No tasks to export');
                return;
            }
            
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'export_tasks.php';
            form.innerHTML = `
                <input type="hidden" name="format" value="${format}">
                <input type="hidden" name="filter" value="${tabName}">
                <input type="hidden" name="search" value="${searchQuery}">
                <input type="hidden" name="data" value='${JSON.stringify(visibleRows)}'>
            `;
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        }
        
        function exportVisits(format) {
            const searchQuery = document.getElementById('visitSearchInput').value;
            const dateFrom = document.getElementById('visitDateFrom').value;
            const dateTo = document.getElementById('visitDateTo').value;
            
            showToast(`Exporting visits as ${format.toUpperCase()}...`);
            
            // Get visible visits
            const visibleVisits = [];
            const visitCards = document.querySelectorAll('.visit-card');
            
            visitCards.forEach(card => {
                if (card.style.display !== 'none') {
                    const date = card.querySelector('.visit-date').textContent.replace('📅 ', '').trim();
                    const time = card.querySelector('.visit-time').textContent.replace('🕒 ', '').trim();
                    const venue = card.querySelector('.visit-venue').textContent.replace('📍 ', '').trim();
                    const reason = card.querySelector('.visit-reason').textContent.trim();
                    
                    visibleVisits.push({
                        date: date,
                        time: time,
                        venue: venue,
                        reason: reason
                    });
                }
            });
            
            if (visibleVisits.length === 0) {
                showToast('No visits to export');
                return;
            }
            
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'export_visits.php';
            form.innerHTML = `
                <input type="hidden" name="format" value="${format}">
                <input type="hidden" name="search" value="${searchQuery}">
                <input type="hidden" name="date_from" value="${dateFrom}">
                <input type="hidden" name="date_to" value="${dateTo}">
                <input type="hidden" name="data" value='${JSON.stringify(visibleVisits)}'>
            `;
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        }
    </script>
</body>
</html>