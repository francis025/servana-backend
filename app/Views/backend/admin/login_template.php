<?php 
$data = get_settings('general_settings', true);
$company_title = get_company_title_with_fallback($data);
isset($data['primary_color']) && $data['primary_color'] != "" ?  $primary_color = $data['primary_color'] : $primary_color =  '#0073f0';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no" name="viewport">
    <title><?= $title ?> &mdash; <?= $company_title; ?></title>
    <!-- Bootstrap 5 CSS from CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, <?= $primary_color ?> 0%, #1a1a2e 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            overflow: hidden;
            max-width: 420px;
            width: 100%;
        }
        .login-header {
            background: <?= $primary_color ?>;
            color: white;
            padding: 30px;
            text-align: center;
        }
        .login-header h2 {
            margin: 0;
            font-weight: 600;
        }
        .login-body {
            padding: 30px;
        }
        .form-control {
            border-radius: 8px;
            padding: 12px 15px;
            border: 1px solid #ddd;
        }
        .form-control:focus {
            border-color: <?= $primary_color ?>;
            box-shadow: 0 0 0 0.2rem rgba(0,115,240,0.15);
        }
        .btn-primary {
            background: <?= $primary_color ?>;
            border: none;
            border-radius: 8px;
            padding: 12px;
            font-weight: 600;
        }
        .btn-primary:hover {
            background: #0056b3;
        }
        .form-label {
            font-weight: 500;
            color: #333;
        }
        .alert {
            border-radius: 8px;
        }
        .logo-img {
            max-height: 60px;
            margin-bottom: 15px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="login-card mx-auto">
                    <div class="login-header">
                        <?php if (isset($data['logo']) && $data['logo'] != "") : ?>
                            <img src="<?= base_url("uploads/site/" . $data['logo']) ?>" class="logo-img" alt="Logo">
                        <?php endif; ?>
                        <h2><i class="fas fa-user-shield me-2"></i>Admin Login</h2>
                    </div>
                    
                    <div class="login-body">
                        <?php if (isset($message) && !empty($message)) : ?>
                            <div class="alert alert-danger">
                                <?php echo $message; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($_SESSION['logout_msg'])) : ?>
                            <div class="alert alert-success">
                                <?= $_SESSION['logout_msg'] ?>
                            </div>
                        <?php endif; ?>

                        <?= form_open('admin/login', ['method' => "post", "id" => "login-form"]); ?>
                        
                        <div class="mb-3">
                            <label for="identity" class="form-label">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input id="identity" type="email" class="form-control" name="identity" placeholder="Enter your email" required autofocus>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input id="password" type="password" class="form-control" name="password" placeholder="Enter your password" required>
                            </div>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" id="remember" name="remember" value="1" class="form-check-input">
                            <label class="form-check-label" for="remember">Remember me</label>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-sign-in-alt me-2"></i>Login
                            </button>
                        </div>
                        
                        <?= form_close() ?>

                        <div class="text-center mt-4 text-muted">
                            <small>Copyright &copy; Servana <?php echo date("Y"); ?></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap 5 JS from CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
