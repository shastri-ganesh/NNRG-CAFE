<!DOCTYPE html>
<html lang="en">

<head>
    <?php include('head.php'); ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="css/login.css" rel="stylesheet">
    <title>About Us | FOODCAVE</title>
    <style>
        body {
            background: #f8f9fa;
            min-height: 100vh;
            font-family: 'Arial', sans-serif;
        }
        
        .main-section {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin: 2rem 0;
        }
        
        .hero-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            min-height: 400px;
            align-items: center;
        }
        
        .hero-image {
            background: linear-gradient(135deg, #e8f4f8 0%, #d1ecf1 100%);
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem;
            height: 100%;
        }
        
        .hero-circle {
            width: 250px;
            height: 250px;
            border-radius: 50%;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }
        
        .hero-circle img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
            display: block;
        }
        
        .hero-circle .placeholder-icon {
            font-size: 4rem;
            color: #3498db;
            display: none;
        }
        
        .hero-content {
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .hero-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 1rem;
            line-height: 1.2;
        }
        
        .hero-subtitle {
            color: #7f8c8d;
            font-size: 1.1rem;
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        
        .cta-button {
            background: #3498db;
            color: white;
            padding: 12px 30px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            display: inline-block;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            width: fit-content;
        }
        
        .cta-button:hover {
            background: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
            color: white;
        }
        
        .features-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 0;
        }
        
        .feature-card {
            padding: 2.5rem;
            position: relative;
            transition: transform 0.3s ease;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
        }
        
        .feature-card.blue {
            background: #3498db;
            color: white;
        }
        
        .feature-card.light {
            background: #ecf0f1;
            color: #2c3e50;
        }
        
        .feature-card.dark {
            background: #2c3e50;
            color: white;
        }
        
        .feature-number {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 1rem;
            opacity: 0.7;
        }
        
        .feature-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        
        .feature-description {
            line-height: 1.6;
            opacity: 0.9;
        }
        
        .team-section {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin: 2rem 0;
            padding: 3rem;
        }
        
        .team-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .team-member {
            text-align: center;
            padding: 2rem;
            border-radius: 15px;
            background: #f8f9fa;
            transition: all 0.3s ease;
        }
        
        .team-member:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .member-photo {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            margin: 0 auto 1.5rem;
            background: #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: #7f8c8d;
            overflow: hidden;
            position: relative;
        }
        
        .member-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
            position: absolute;
            top: 0;
            left: 0;
        }
        
        .member-photo .placeholder-icon {
            font-size: 2rem;
            color: #7f8c8d;
            z-index: 1;
        }
        
        .member-name {
            font-size: 1.4rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }
        
        .member-role {
            color: #7f8c8d;
            margin-bottom: 1rem;
            font-style: italic;
        }
        
        .member-links {
            display: flex;
            justify-content: center;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .member-link {
            color: #3498db;
            text-decoration: none;
            font-weight: 500;
            padding: 8px 16px;
            border: 2px solid #3498db;
            border-radius: 20px;
            transition: all 0.3s ease;
            display: inline-block;
        }
        
        .member-link:hover {
            background: #3498db;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
        }
        
        .college-info {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            text-align: center;
            margin: 2rem 0;
        }
        
        .college-info h3 {
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }
        
        .college-info p {
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
        }
        
        .return-btn {
            background: #27ae60;
            color: white;
            padding: 12px 30px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            display: inline-block;
            transition: all 0.3s ease;
            margin-top: 2rem;
        }
        
        .return-btn:hover {
            background: #229954;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(39, 174, 96, 0.3);
            color: white;
        }
        
        @media (max-width: 768px) {
            .hero-container {
                grid-template-columns: 1fr;
            }
            
            .hero-image {
                order: 2;
                padding: 2rem;
            }
            
            .hero-circle {
                width: 200px;
                height: 200px;
            }
            
            .hero-title {
                font-size: 2rem;
            }
            
            .features-container {
                grid-template-columns: 1fr;
            }
            
            .team-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body class="d-flex flex-column h-100">
    <header class="navbar navbar-expand-md navbar-light fixed-top bg-light shadow-sm mb-auto">
        <div class="container-fluid mx-4">
            <a href="index.php">
                <img src="img/Color logo - no background.png" width="125" class="me-2" alt="FOODCAVE Logo">
            </a>
            <button class="navbar-toggler collapsed" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false"
                aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="navbar-collapse collapse" id="navbarCollapse">
                <div class="d-flex text-end"></div>
            </div>
        </div>
    </header>

    <div class="container mt-5 pt-5">
        <!-- Main Hero Section -->
        <div class="main-section">
            <div class="hero-container">
                <div class="hero-image">
                    <div class="hero-circle">
                        <img src="img/team-photo.jpg" alt="Team Photo" 
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                        <i class="bi bi-people-fill placeholder-icon"></i>
                    </div>
                </div>
                <div class="hero-content">
                    <h1 class="hero-title">About Our Team</h1>
                    <p class="hero-subtitle">
                        We are The Alpha Achievers, a passionate team of computer science students 
                        dedicated to creating innovative solutions in food technology and web development.
                    </p>
                    <a href="#team" class="cta-button">Meet Our Team</a>
                </div>
            </div>
            
            <!-- Features Section -->
            <div class="features-container">
                <div class="feature-card blue">
                    <div class="feature-number">01</div>
                    <h3 class="feature-title">Innovation</h3>
                    <p class="feature-description">
                        We leverage cutting-edge technologies to create modern web applications 
                        that solve real-world problems in the food industry.
                    </p>
                </div>
                <div class="feature-card light">
                    <div class="feature-number">02</div>
                    <h3 class="feature-title">Collaboration</h3>
                    <p class="feature-description">
                        Our team works together seamlessly, combining diverse skills in 
                        development, design, and user experience to deliver exceptional results.
                    </p>
                </div>
                <div class="feature-card dark">
                    <div class="feature-number">03</div>
                    <h3 class="feature-title">Excellence</h3>
                    <p class="feature-description">
                        We are committed to delivering high-quality solutions that exceed 
                        expectations and create meaningful impact in the digital world.
                    </p>
                </div>
            </div>
        </div>

        <!-- Team Members Section -->
        <div class="team-section" id="team">
            <h2 style="text-align: center; color: #2c3e50; margin-bottom: 1rem;">Meet The Alpha Achievers</h2>
            <p style="text-align: center; color: #7f8c8d; margin-bottom: 2rem;">
                The talented individuals behind FOODCAVE
            </p>
            
            <div class="team-grid">
                <div class="team-member">
                    <div class="member-photo">
                        <img src="img/gan.jpg" alt="Ganesh Bijapurkar" 
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                    
                    </div>
                    <h3 class="member-name">Ganesh Bijapurkar</h3>
                    <p class="member-role">Full Stack Developer & UI Designer</p>
                    <div class="member-links">
                        <a href="https://www.linkedin.com/in/ganesh-bijapurkar/" class="member-link" target="_blank">
                            <i class="bi bi-linkedin me-1"></i>LinkedIn
                        </a>
                        <a href="https://github.com/shastri-ganesh" class="member-link" target="_blank">
                            <i class="bi bi-github me-1"></i>GitHub
                        </a>
                    </div>
                </div>
                
                <div class="team-member">
                    <div class="member-photo">
                        <img src="img/vams.jpg" alt="Vamshi Yamajala" 
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                    </div>
                    <h3 class="member-name">Vamshi Yamajala</h3>
                    <p class="member-role">Full Stack Developer & UI Designer</p>
                    <div class="member-links">
                        <a href="https://www.linkedin.com/in/vamshiyamjala/" class="member-link" target="_blank">
                            <i class="bi bi-linkedin me-1"></i>LinkedIn
                        </a>
                        <a href="https://github.com/vamshi137" class="member-link" target="_blank">
                            <i class="bi bi-github me-1"></i>GitHub
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- College Information -->
        <div class="college-info">
            <h3>NNRG College of Engineering</h3>
            <p>Department of Computer Science and Engineering</p>
        </div>

        <!-- Updated: Return to Home Button with space before footer -->
<div class="text-center" style="margin-bottom: 3rem;">
    <a href="index.php" class="return-btn">
        <i class="bi bi-arrow-left me-2"></i>Return to Home
    </a>
</div>

    </div>

    <?php include('footer.php')?>
</body>

</html>