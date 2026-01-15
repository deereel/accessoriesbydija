<?php
$page_title = "Size Guide";
$page_description = "Find your perfect jewelry size with our comprehensive size guide for rings, bracelets, necklaces, and more.";
include 'includes/header.php';
?>

<main>
    <section class="size-guide-hero">
        <div class="container">
            <h1>Jewelry Size Guide</h1>
            <p>Find your perfect fit with our detailed sizing information for all jewelry types.</p>
        </div>
    </section>

    <section class="size-guide-content">
        <div class="container">
            <div class="size-tabs">
                <button class="tab-btn active" data-tab="rings">Rings</button>
                <button class="tab-btn" data-tab="bracelets">Bracelets</button>
                <button class="tab-btn" data-tab="necklaces">Necklaces</button>
                <button class="tab-btn" data-tab="earrings">Earrings</button>
            </div>

            <div class="tab-content active" id="rings">
                <h2>Ring Sizes</h2>
                <p>Measure your finger at the end of the day when it's warm. Fingers can shrink when cold.</p>

                <div class="size-chart">
                    <table>
                        <thead>
                            <tr>
                                <th>US Size</th>
                                <th>UK Size</th>
                                <th>EU Size</th>
                                <th>Inside Diameter (mm)</th>
                                <th>Inside Circumference (mm)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td>4</td><td>H</td><td>47</td><td>14.9</td><td>46.5</td></tr>
                            <tr><td>5</td><td>J</td><td>49</td><td>15.7</td><td>49.3</td></tr>
                            <tr><td>6</td><td>L</td><td>52</td><td>16.5</td><td>51.9</td></tr>
                            <tr><td>7</td><td>N</td><td>54</td><td>17.3</td><td>54.4</td></tr>
                            <tr><td>8</td><td>P</td><td>57</td><td>18.1</td><td>57.0</td></tr>
                            <tr><td>9</td><td>R</td><td>59</td><td>18.9</td><td>59.5</td></tr>
                            <tr><td>10</td><td>T</td><td>62</td><td>19.8</td><td>62.1</td></tr>
                        </tbody>
                    </table>
                </div>

                <div class="measurement-guide">
                    <h3>How to Measure</h3>
                    <div class="measurement-methods">
                        <div class="method">
                            <h4>Method 1: String Method</h4>
                            <ol>
                                <li>Wrap a string around your finger</li>
                                <li>Mark where the string overlaps</li>
                                <li>Measure the length with a ruler</li>
                                <li>Compare to the circumference column</li>
                            </ol>
                        </div>
                        <div class="method">
                            <h4>Method 2: Ring Method</h4>
                            <ol>
                                <li>Take a ring that fits well</li>
                                <li>Measure the inside diameter</li>
                                <li>Compare to the diameter column</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-content" id="bracelets">
                <h2>Bracelet Sizes</h2>
                <p>Bracelet sizes are measured by wrist circumference. Add 1-2 cm for comfort.</p>

                <div class="size-chart">
                    <table>
                        <thead>
                            <tr>
                                <th>Size</th>
                                <th>Wrist Circumference (cm)</th>
                                <th>Wrist Circumference (inches)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td>Small</td><td>14-15</td><td>5.5-5.9</td></tr>
                            <tr><td>Medium</td><td>15-16</td><td>5.9-6.3</td></tr>
                            <tr><td>Large</td><td>16-17</td><td>6.3-6.7</td></tr>
                            <tr><td>X-Large</td><td>17-18</td><td>6.7-7.1</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="tab-content" id="necklaces">
                <h2>Necklace Sizes</h2>
                <p>Necklace length is measured from end to end, including the clasp.</p>

                <div class="size-chart">
                    <table>
                        <thead>
                            <tr>
                                <th>Length</th>
                                <th>Style</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td>14"</td><td>Choker</td><td>Sits close to the neck</td></tr>
                            <tr><td>16"</td><td>Princess</td><td>Most popular length</td></tr>
                            <tr><td>18"</td><td>Matinee</td><td>Falls at collarbone</td></tr>
                            <tr><td>20"</td><td>Opera</td><td>Falls below collarbone</td></tr>
                            <tr><td>24"+</td><td>Rope</td><td>Long, dramatic length</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="tab-content" id="earrings">
                <h2>Earring Sizes</h2>
                <p>Earrings are measured by the diameter of the post or wire.</p>

                <div class="size-info">
                    <p>Most earrings use standard post sizes. Specialty earrings may have different measurements.</p>
                    <ul>
                        <li><strong>Post Diameter:</strong> 0.8mm - 1.0mm</li>
                        <li><strong>Wire Thickness:</strong> 0.5mm - 0.8mm</li>
                        <li><strong>Hoop Diameter:</strong> 10mm - 50mm</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>
</main>

<style>
.size-guide-hero {
    background: linear-gradient(135deg, #f8f8f8 0%, #e8e8e8 100%);
    padding: 4rem 0;
    text-align: center;
}

.size-guide-hero h1 {
    font-size: 2.5rem;
    margin-bottom: 1rem;
    color: #333;
}

.size-guide-hero p {
    font-size: 1.2rem;
    color: #666;
    max-width: 600px;
    margin: 0 auto;
}

.size-guide-content {
    padding: 4rem 0;
}

.size-tabs {
    display: flex;
    justify-content: center;
    gap: 1rem;
    margin-bottom: 3rem;
    flex-wrap: wrap;
}

.tab-btn {
    padding: 0.75rem 1.5rem;
    border: 2px solid #C27BA0;
    background: white;
    color: #C27BA0;
    border-radius: 25px;
    cursor: pointer;
    transition: all 0.3s;
    font-weight: 600;
}

.tab-btn.active,
.tab-btn:hover {
    background: #C27BA0;
    color: white;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

.tab-content h2 {
    font-size: 1.8rem;
    margin-bottom: 1rem;
    color: #333;
}

.tab-content > p {
    color: #666;
    margin-bottom: 2rem;
    font-size: 1.1rem;
}

.size-chart {
    margin-bottom: 3rem;
    overflow-x: auto;
}

.size-chart table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    border-radius: 8px;
    overflow: hidden;
}

.size-chart th,
.size-chart td {
    padding: 1rem;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.size-chart th {
    background: #f8f8f8;
    font-weight: 600;
    color: #333;
}

.size-chart tr:nth-child(even) {
    background: #f9f9f9;
}

.measurement-guide h3 {
    font-size: 1.4rem;
    margin-bottom: 1.5rem;
    color: #333;
}

.measurement-methods {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
}

.method h4 {
    color: #333;
    margin-bottom: 1rem;
}

.method ol {
    color: #666;
    line-height: 1.6;
}

.method li {
    margin-bottom: 0.5rem;
}

.size-info {
    background: #f8f8f8;
    padding: 2rem;
    border-radius: 8px;
}

.size-info p {
    color: #666;
    margin-bottom: 1rem;
}

.size-info ul {
    color: #666;
    padding-left: 1.5rem;
}

.size-info li {
    margin-bottom: 0.5rem;
}

@media (max-width: 768px) {
    .size-tabs {
        flex-direction: column;
        align-items: center;
    }

    .tab-btn {
        width: 200px;
    }

    .measurement-methods {
        grid-template-columns: 1fr;
        gap: 1rem;
    }

    .size-guide-hero h1 {
        font-size: 2rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tabBtns = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');

    tabBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            // Remove active class from all buttons and contents
            tabBtns.forEach(b => b.classList.remove('active'));
            tabContents.forEach(c => c.classList.remove('active'));

            // Add active class to clicked button and corresponding content
            this.classList.add('active');
            const tabId = this.dataset.tab;
            document.getElementById(tabId).classList.add('active');
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>