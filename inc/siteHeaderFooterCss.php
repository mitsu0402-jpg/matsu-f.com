.site-header {
    position: relative;
    min-height: 400px;
    background-image: url('/image/header_bg.jpg');
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    color: #0f1f2e;
    font-family: Monda, Helvetica, Arial, Sans-Serif, serif;
}

.site-header::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(to bottom, rgba(255, 255, 255, 0.12), rgba(255, 255, 255, 0.08));
}

.site-header-inner {
    position: relative;
    z-index: 1;
    max-width: 1200px;
    margin: 0 auto;
    padding: 18px 28px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
}

.site-logo {
    display: inline-flex;
    align-items: center;
    gap: 14px;
    color: #0a1220;
    text-decoration: none;
    font-size: 28px;
    font-weight: 800;
    letter-spacing: 0.02em;
    text-shadow: 0 1px 0 rgba(255, 255, 255, 0.35);
}

.site-logo img {
    width: 72px;
    height: 72px;
    object-fit: contain;
    flex: 0 0 auto;
}

.menu-toggle {
    display: none;
    position: relative;
    z-index: 2;
    width: 44px;
    height: 44px;
    padding: 0;
    border: 1px solid rgba(10, 18, 32, 0.25);
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.9);
    align-items: center;
    justify-content: center;
    flex-direction: column;
    gap: 5px;
    cursor: pointer;
}

.menu-toggle span {
    display: block;
    width: 22px;
    height: 2px;
    background: #0a1220;
    border-radius: 2px;
}

.site-nav {
    display: flex;
    align-items: center;
    gap: 34px;
    flex-wrap: wrap;
    justify-content: flex-end;
}

.site-nav a {
    color: #0a1220;
    text-decoration: none;
    font-size: 14px;
    font-weight: 700;
    letter-spacing: 0.03em;
    border-bottom: 3px solid transparent;
    padding-bottom: 6px;
}

.site-nav a:hover,
.site-nav a.is-active {
    border-bottom-color: #124b32;
}

.site-hero-title {
    position: relative;
    z-index: 1;
    margin: 100px auto;
    text-align: center;
    font-size: 22px;
    font-weight: 800;
    letter-spacing: 0.08em;
    color: #0a1220;
    text-shadow: 2px 1px 2px rgba(255, 255, 255, 0.45);
}

.site-main {
    padding-top: 20px;
}

.site-footer {
    border-top: 1px solid #e8e2dc;
    margin-top: 28px;
    background: #124b32;
    font-family: Monda, Helvetica, Arial, Sans-Serif, serif;
    color: #ffffff;
}

.site-footer-inner {
    margin: 0 auto;
    padding: 18px 16px 22px;
    color: #ffffff;
    font-size: 13px;
    line-height: 1.6;
    text-align: center;
}

@media (max-width: 900px) {
    .site-header {
        min-height: 400px;
    }

    .site-header-inner {
        flex-direction: column;
        align-items: flex-start;
        padding: 14px 14px 0;
    }

    .site-logo {
        font-size: 24px;
    }

    .site-logo img {
        width: 56px;
        height: 56px;
    }

    .menu-toggle {
        display: inline-flex;
        position: absolute;
        top: 14px;
        right: 14px;
    }

    .site-nav {
        display: none;
        width: 100%;
        flex-direction: column;
        align-items: flex-start;
        justify-content: flex-start;
        gap: 8px;
        padding: 10px 12px;
        background: rgba(255, 255, 255, 0.92);
        border-radius: 8px;
    }

    .site-nav.is-open {
        display: flex;
    }

    .site-nav a {
        font-size: 15px;
        padding-bottom: 2px;
    }

    .site-hero-title {
        margin-top: 18px;
        font-size: 24px;
    }
}
