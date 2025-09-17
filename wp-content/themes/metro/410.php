<?php
status_header(410);
get_header();

?>

<style>
    .error-section {
        padding: 100px 0;
        background: var(--mm-blue-grey-color)
    }

    .error-section .content {
        background: var(--mm-primary-bg-color);
        box-shadow: 0 2px 8px #0000001a;
        border-radius: var(--mm-border-radius);
        border: 1px solid var(--mm-border-color);
        padding: 40px 20px 80px;
        display: flex;
        align-items: center;
        flex-direction: column;
        text-align: center
    }

    .error-section .content .image {
        width: 200px;
        height: 200px;
        display: flex;
        align-items: center;
        flex-shrink: 0;
        justify-content: center;
        margin-bottom: 20px
    }

    .error-section .content .image img {
        width: 163px;
        height: 160px
    }

    .error-section .content h1 {
        font-size: 32px;
        margin-bottom: 20px
    }

    .error-section .content p {
        color: var(--mm-grey-color);
        margin-bottom: 40px
    }

    .error-section .content a {
        font-weight: 700;
        color: var(--mm-blue-color);
        border: 1px solid var(--mm-blue-color);
        border-radius: var(--mm-button-radius);
        padding: 14.61px 30px
    }

    .error-section .content a:hover {
        background: linear-gradient(0deg, rgba(0, 0, 0, .04), rgba(0, 0, 0, .04)), var(--mm-white-color)
    }

    @media (max-width: 576px) {
        .error-section {
            padding: 60px 0
        }

        .error-section .content .image {
            margin-bottom: 20px
        }

        .error-section .content h1 {
            font-size: 20px
        }
    }
    .button-container {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 10px;
    }
    
    .action-buttons a {
        display: block;
        padding: 15px;
        color: white;
        text-align: center;
        border-radius: 5px;
        text-decoration: none;
    }
    
    @media (max-width: 768px) {
        .button-container {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    
    @media (max-width: 480px) {
        .button-container {
            grid-template-columns: 1fr;
        }
    }
</style>

<?php
$image = get_field('image_error_page', 'option');
$title = 'Page Removed';
$text = 'Sorry, the page you are looking for has been permanently removed. Please use the navigation below to find what you are looking for.';
?>

<main>
    <section class="error-section">
        <div class="container">
            <div class="content">
                <?php if ($image) : ?>
                    <div class="image">
                        <?php echo wp_get_attachment_image($image['id'], 'full', '', ['loading' => 'lazy']); ?>
                    </div>
                <?php endif; ?>
                <?php if ($title) : ?>
                    <h1><?php echo $title; ?></h1>
                <?php endif; ?>
                <?php if ($text) : ?>
                    <p><?php echo $text; ?></p>
                <?php endif; ?>
                <div class="button-container">
                    <div class="action-buttons"><a aria-label="Home" href="/">Homepage</a>
                    </div>
                    <div class="action-buttons"><a aria-label="Listings" href="https://www.metro-manhattan.com/commercial-space/">View Listings</a>
                    </div>
                    <div class="action-buttons"><a aria-label="News" href="https://www.metro-manhattan.com/blog/">News & Articles</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php
get_footer();
