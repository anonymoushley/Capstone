<style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #1e40af;
            --accent-color: #3b82f6;
            --light-color: #eff6ff;
            --dark-color: #1e3a8a;
            --gray-color: #f1f5f9;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f8fafc;
        }
        
        header {
            background-color: var(--primary-color);
            color: white;
            padding: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 1rem;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        
        main {
            padding: 2rem 0;
        }
        
        
        .programs-container {
            margin-bottom: 2rem;
        }
        
        .section-title {
            margin-bottom: 1rem;
            color: var(--dark-color);
            font-size: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--accent-color);
        }
        
        .program-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .program-card {
            background-color: white;
            border-radius: 0.5rem;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
        
        }
        
        .program-card:hover {
            transform: translateY(-5px);
        }
        
        .program-image {
            height: 150px;
            background-color: var(--gray-color);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-color);
            font-size: 3rem;
        }
        
        .program-content {
            padding: 1.5rem;
        }
        
        .program-title {
            font-size: 1.25rem;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
        }
        
        .program-description {
            color: #4b5563;
            margin-bottom: 1rem;
            line-height: 1.5;
        }
        
        .program-details {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .program-detail {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            font-size: 0.875rem;
            color: #64748b;
        }
           
        .program-image img{width:23%;}
    </style>
<section id="programs" class="programs-container">
                <h2 class="section-title">College of Computer Studies</h2>
                <div class="program-cards">
                    <div class="program-card">
                        <div class="program-image">
                            <img src="images/ccs.png" alt="CHMSU Logo">
                        </div>
                        <div class="program-content">
                            <h3 class="program-title">BS Information Technology</h3>
                            <div class="program-details">
                                <div class="program-detail">
                                    <span>⏱️</span>
                                    <span>4 Years</span>
                                </div>
                                
                            </div>
                            <h7>Availability: <i> Alijis Campus, Binalbagan Campus</i></h7>
                            <p class="program-description mt-2">
                                The Bachelor of Science in Information Technology program focuses on the practical applications of computing technology in business and organizational settings. Students learn to design, implement, and manage IT systems that support business operations and strategic initiatives.
                            </p>
                            
                        </div>
                    </div>
                    
                    <div class="program-card">
                        <div class="program-image">
                            <img src="images/ccs.png" alt="CHMSU Logo">
                        </div>
                        <div class="program-content">
                            <h3 class="program-title">BS Information Systems</h3>
                            <div class="program-details">
                                <div class="program-detail">
                                    <span>⏱️</span>
                                    <span>4 Years</span>
                                </div>
                                
                                </div>
                                <h7>Availability: <i> Talisay Campus, Alijis Campus, Fortune Towne Campus</i></h7>
                            <p class="program-description mt-2">
                                The Bachelor of Science in Information Systems program bridges the gap between business and technology. Students learn how information systems can be leveraged to improve organizational processes, support decision-making, and drive business strategy.
                            </p>
                                                    
                        </div>
                    </div>
                </div>
            </section>