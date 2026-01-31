<!-- Admin Login Page -->
<section class="container" data-aos='fade-up'>
    <div class="row">
        <div id="app">
            <section class="section">
                <div class="container mt-5">
                    <div class="row">
                        <div class="col-12 col-sm-8 offset-sm-2 col-md-6 offset-md-3 col-lg-6 offset-lg-3 col-xl-4 offset-xl-4">
                            <?php
                            $data = get_settings('general_settings', true);
                            ?>

                            <?php if (isset($message) && !empty($message)) : ?>
                                <?php echo $message; ?>
                            <?php endif; ?>
                            
                            <?php if (isset($_SESSION['logout_msg'])) : ?>
                                <div class="alert alert-primary" id="logout_msg">
                                    <?= $_SESSION['logout_msg'] ?>
                                </div>
                            <?php endif; ?>

                            <div class="card card-primary shadow-lg bg-white rounded">
                                <img src="<?= isset($data['logo']) && $data['logo'] != "" ? base_url("public/uploads/site/" . $data['logo']) : base_url('public/backend/assets/img/news/img01.jpg') ?>" class="" alt="">
                                <div class="card-header">
                                    <h3 class="text-primary">Admin Login</h3>
                                </div>

                                <div class="card-body">
                                    <?= form_open('auth/login', ['method' => "post", "class" => "", "id" => "login-form"]); ?>
                                    <div class="form-group">
                                        <label for="identity">Email Address</label>
                                        <input id="identity" type="email" class="form-control" name="identity" tabindex="1" placeholder="Enter your email address" required autofocus>
                                        <div class="invalid-feedback">
                                            Please fill in your email address
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <div class="d-block">
                                            <label for="password" class="control-label">Password</label>
                                            <div class="float-end">
                                                <a href="<?= base_url('auth/forgot-password') ?>" class="text-small text-primary">
                                                    Forgot Password?
                                                </a>
                                            </div>
                                        </div>
                                        <input id="password" type="password" class="form-control" name="password" tabindex="2" required placeholder="Enter your password">
                                        <div class="invalid-feedback">
                                            Please fill in your password
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" id="remember" name='remember' value=1 class="form-check-input" />
                                            <label class="form-check-label" for="remember">Remember me</label>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary btn-lg w-100" tabindex="4">
                                            Login
                                        </button>
                                    </div>
                                    <?= form_close() ?>

                                    <div class="simple-footer mb-1 text-center mt-3">
                                        Copyright &copy; Servana <?php echo date("Y"); ?>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</section>
