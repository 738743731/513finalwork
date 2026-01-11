<?php
// 包含认证文件
require_once 'includes/auth.php';

// 检查用户是否已认证
checkAuthentication();

// 获取当前用户信息
$current_user = getCurrentUser();

// 设置页面标题
$page_title = "Forum";

// 包含数据库和头部文件
require_once 'includes/database.php';
require_once 'includes/header.php';

// 初始化变量
$error = '';
$success = '';

try {
    // 创建数据库实例
    $db = new Database();
    
    // 处理新帖子提交
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // 处理新帖子
        if (isset($_POST['post_content'])) {
            $content = trim($_POST['post_content'] ?? '');
            $user_id = $current_user['id'];
            
            // 验证内容
            if (empty($content)) {
                $error = "Post content cannot be empty!";
            } elseif (strlen($content) < 5) {
                $error = "Post content must be at least 5 characters long!";
            } elseif (strlen($content) > 2000) {
                $error = "Post content cannot exceed 2000 characters!";
            } else {
                // 插入新帖子
                $sql = "INSERT INTO forum_posts (user_id, content) VALUES (?, ?)";
                $db->query($sql, [$user_id, $content]);
                
                $success = "Your post has been published successfully!";
                
                // 清除表单内容
                unset($_POST['post_content']);
            }
        }
        
        // 处理删除帖子
        if (isset($_POST['delete_post_id'])) {
            $post_id = intval($_POST['delete_post_id']);
            $user_id = $current_user['id'];
            
            // 验证用户权限（只能删除自己的帖子）
            $check_sql = "SELECT user_id FROM forum_posts WHERE id = ?";
            $post = $db->fetchOne($check_sql, [$post_id]);
            
            if ($post && $post['user_id'] == $user_id) {
                $delete_sql = "DELETE FROM forum_posts WHERE id = ?";
                $db->query($delete_sql, [$post_id]);
                $success = "Post deleted successfully!";
            } else {
                $error = "You can only delete your own posts!";
            }
        }
    }
    
    // 获取所有帖子
    $posts = [];
    try {
        // 直接使用JOIN查询，不依赖视图
        $sql = "SELECT 
                    fp.id,
                    fp.user_id,
                    fp.content,
                    fp.created_at,
                    s.first_name, 
                    s.last_name, 
                    s.email,
                    CONCAT(s.first_name, ' ', s.last_name) as display_name
                FROM 
                    forum_posts fp
                LEFT JOIN 
                    wp9k_fc_subscribers s ON fp.user_id = s.id
                ORDER BY 
                    fp.created_at DESC";
        
        $posts = $db->fetchAll($sql);
        
    } catch (Exception $e) {
        // 如果JOIN失败，尝试只获取帖子数据
        error_log("Forum query error: " . $e->getMessage());
        
        try {
            $sql = "SELECT * FROM forum_posts ORDER BY created_at DESC";
            $posts = $db->fetchAll($sql);
            
            // 添加用户信息占位符
            foreach ($posts as &$post) {
                $post['first_name'] = 'User';
                $post['last_name'] = '';
                $post['display_name'] = 'User ' . $post['user_id'];
            }
            
        } catch (Exception $e2) {
            $error = "Unable to load forum posts. Please try again later.";
            error_log("Fallback forum query error: " . $e2->getMessage());
            $posts = [];
        }
    }
    
} catch (Exception $e) {
    $error = "Database connection error. Please try again later.";
    error_log("Forum database error: " . $e->getMessage());
    $posts = [];
}

// 获取并显示任何会话消息
$message = getMessage();
?>

<div class="container forum-container">
    <h1><i class="fas fa-comments"></i> Community Forum</h1>
    <p class="forum-description">Welcome to our community forum! Share your thoughts, ask questions, and connect with other gamers.</p>
    
    <!-- 显示会话消息 -->
    <?php if ($message): ?>
        <div class="alert alert-<?php echo htmlspecialchars($message['type']); ?>">
            <i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($message['text']); ?>
        </div>
    <?php endif; ?>
    
    <!-- 显示错误消息 -->
    <?php if ($error): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <!-- 显示成功消息 -->
    <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>
    
    <div class="forum-content">
        <!-- 创建新帖子表单 -->
        <div class="new-post-card">
            <h2><i class="fas fa-edit"></i> Create New Post</h2>
            <form method="POST" action="" id="postForm">
                <div class="form-group">
                    <label for="post_content">Your Message:</label>
                    <textarea id="post_content" name="post_content" rows="5" 
                              placeholder="What's on your mind? Share your thoughts with the community..."
                              maxlength="2000"
                              required><?php echo isset($_POST['post_content']) ? htmlspecialchars($_POST['post_content']) : ''; ?></textarea>
                    <div class="char-counter">
                        <span id="charCount">0</span> / 2000 characters
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" name="submit_post" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Publish Post
                    </button>
                    <button type="reset" class="btn btn-secondary">
                        <i class="fas fa-redo"></i> Clear
                    </button>
                </div>
            </form>
        </div>
        
        <!-- 帖子列表 -->
        <div class="posts-section">
            <h2><i class="fas fa-list"></i> Recent Discussions</h2>
            
            <?php if (empty($posts)): ?>
                <div class="empty-state">
                    <i class="fas fa-comments-slash"></i>
                    <h3>No posts yet</h3>
                    <p>Be the first to start a discussion! Share your thoughts with the community.</p>
                </div>
            <?php else: ?>
                <div class="posts-list">
                    <?php foreach ($posts as $post): ?>
                        <div class="post-card">
                            <div class="post-header">
                                <div class="user-info">
                                    <div class="user-avatar">
                                        <?php 
                                        // 显示用户名字首字母
                                        if (!empty($post['first_name'])) {
                                            $first_letter = strtoupper(substr($post['first_name'], 0, 1));
                                        } elseif (!empty($post['display_name'])) {
                                            $first_letter = strtoupper(substr($post['display_name'], 0, 1));
                                        } else {
                                            $first_letter = 'U';
                                        }
                                        echo $first_letter;
                                        ?>
                                    </div>
                                    <div class="user-details">
                                        <div class="user-name">
                                            <?php 
                                            if (!empty($post['display_name'])) {
                                                echo htmlspecialchars($post['display_name']);
                                            } elseif (!empty($post['first_name'])) {
                                                echo htmlspecialchars($post['first_name'] . ' ' . ($post['last_name'] ?? ''));
                                            } else {
                                                echo 'User ' . $post['user_id'];
                                            }
                                            ?>
                                            <?php if (isset($post['user_id']) && $post['user_id'] == $current_user['id']): ?>
                                                <span class="badge-you">You</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="post-time">
                                            <i class="far fa-clock"></i> 
                                            <?php 
                                            if (!empty($post['created_at'])) {
                                                echo date('F j, Y \a\t H:i', strtotime($post['created_at']));
                                            } else {
                                                echo 'Recently';
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </div>
                                <?php if (isset($post['user_id']) && $post['user_id'] == $current_user['id']): ?>
                                    <div class="post-actions">
                                        <form method="POST" action="" class="delete-form" onsubmit="return confirm('Are you sure you want to delete this post?');">
                                            <input type="hidden" name="delete_post_id" value="<?php echo $post['id']; ?>">
                                            <button type="submit" class="btn-icon" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="post-content">
                                <?php echo nl2br(htmlspecialchars($post['content'] ?? 'No content')); ?>
                            </div>
                            
                            <div class="post-footer">
                                <span class="post-id">
                                    Post #<?php echo $post['id']; ?>
                                </span>
                                <span class="reply-count">
                                    <i class="far fa-comment"></i> 0 replies
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.forum-container {
    max-width: 1000px;
    margin: 0 auto;
    padding: 20px;
}

.forum-description {
    color: #666;
    margin-bottom: 30px;
    font-size: 16px;
    line-height: 1.6;
}

.forum-content {
    display: flex;
    flex-direction: column;
    gap: 30px;
}

.new-post-card {
    background: white;
    border-radius: 10px;
    padding: 25px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.new-post-card h2 {
    margin-top: 0;
    margin-bottom: 20px;
    color: #333;
    display: flex;
    align-items: center;
    gap: 10px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #555;
}

.form-group textarea {
    width: 100%;
    padding: 15px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 16px;
    font-family: inherit;
    resize: vertical;
    min-height: 120px;
}

.form-group textarea:focus {
    outline: none;
    border-color: #007bff;
    box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
}

.char-counter {
    text-align: right;
    font-size: 12px;
    color: #666;
    margin-top: 5px;
}

.form-actions {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}

.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    font-size: 16px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s;
}

.btn-primary {
    background: #007bff;
    color: white;
}

.btn-primary:hover {
    background: #0056b3;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #545b62;
}

.posts-section h2 {
    color: #333;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.empty-state {
    text-align: center;
    padding: 50px 20px;
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.empty-state i {
    font-size: 60px;
    color: #ddd;
    margin-bottom: 20px;
}

.empty-state h3 {
    color: #666;
    margin-bottom: 10px;
}

.empty-state p {
    color: #999;
    max-width: 400px;
    margin: 0 auto;
}

.posts-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.post-card {
    background: white;
    border-radius: 10px;
    padding: 25px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    transition: transform 0.3s, box-shadow 0.3s;
}

.post-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.post-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 20px;
}

.user-info {
    display: flex;
    gap: 15px;
    align-items: center;
}

.user-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 24px;
    font-weight: bold;
    flex-shrink: 0;
}

.user-details {
    flex: 1;
}

.user-name {
    font-weight: 600;
    color: #333;
    margin-bottom: 5px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.badge-you {
    background: #28a745;
    color: white;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: normal;
}

.post-time {
    color: #666;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 5px;
}

.post-actions {
    display: flex;
    gap: 5px;
}

.delete-form {
    display: inline;
}

.btn-icon {
    background: none;
    border: none;
    color: #666;
    cursor: pointer;
    padding: 5px;
    border-radius: 4px;
    transition: all 0.3s;
}

.btn-icon:hover {
    color: #dc3545;
    background: #f8f9fa;
}

.post-content {
    color: #333;
    line-height: 1.6;
    margin-bottom: 20px;
    font-size: 16px;
    white-space: pre-wrap;
    word-break: break-word;
}

.post-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 15px;
    border-top: 1px solid #eee;
}

.post-id {
    font-size: 12px;
    color: #999;
}

.reply-count {
    font-size: 12px;
    color: #666;
    display: flex;
    align-items: center;
    gap: 5px;
}

.alert {
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 5px;
    border: 1px solid transparent;
}

.alert-error {
    background-color: #f8d7da;
    border-color: #f5c6cb;
    color: #721c24;
}

.alert-success {
    background-color: #d4edda;
    border-color: #c3e6cb;
    color: #155724;
}

.alert-info {
    background-color: #d1ecf1;
    border-color: #bee5eb;
    color: #0c5460;
}

@media (max-width: 768px) {
    .forum-container {
        padding: 15px;
    }
    
    .new-post-card,
    .post-card {
        padding: 20px;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .btn {
        width: 100%;
        justify-content: center;
    }
    
    .user-avatar {
        width: 40px;
        height: 40px;
        font-size: 18px;
    }
    
    .post-header {
        flex-direction: column;
        gap: 10px;
    }
    
    .post-actions {
        align-self: flex-end;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 字符计数器
    const textarea = document.getElementById('post_content');
    const charCount = document.getElementById('charCount');
    
    if (textarea && charCount) {
        // 初始化计数器
        charCount.textContent = textarea.value.length;
        
        // 实时更新计数器
        textarea.addEventListener('input', function() {
            charCount.textContent = this.value.length;
            
            // 如果接近限制，改变颜色
            if (this.value.length > 1800) {
                charCount.style.color = '#dc3545';
            } else if (this.value.length > 1500) {
                charCount.style.color = '#ffc107';
            } else {
                charCount.style.color = '#666';
            }
        });
        
        // 自动调整高度
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
    }
    
    // 表单提交验证
    const postForm = document.getElementById('postForm');
    if (postForm) {
        postForm.addEventListener('submit', function(e) {
            const content = textarea.value.trim();
            
            if (content.length < 5) {
                e.preventDefault();
                alert('Post content must be at least 5 characters long.');
                textarea.focus();
                return false;
            }
            
            if (content.length > 2000) {
                e.preventDefault();
                alert('Post content cannot exceed 2000 characters.');
                textarea.focus();
                return false;
            }
            
            return true;
        });
    }
    
    // 添加动画效果
    const postCards = document.querySelectorAll('.post-card');
    postCards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
});
</script>

<?php 
require_once 'includes/footer.php';
?>