#!/bin/bash

echo "🎯 Setting up realistic SEO test scenarios for Products, Pages, and Posts..."
echo ""

# ============================================
# PRODUCTS - Add intentionally bad SEO meta
# ============================================
echo "📦 Processing Products..."
docker compose run --rm wpcli wp post list --post_type=product --format=csv --fields=ID,post_title 2>/dev/null | tail -n +2 | while IFS=, read -r ID TITLE; do
    
    case "$TITLE" in
        *"Sony"*|*"Headphones"*|*"WH-1000XM5"*)
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_title "Sony Headphones" 2>/dev/null
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_metadesc "Buy this product now" 2>/dev/null
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_focuskw "headphones" 2>/dev/null
            echo "  ✓ Sony Headphones - Added poor SEO (title too short, generic CTA)"
            ;;
        *"Desk"*|*"Standing"*|*"FlexiDesk"*)
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_title "Standing desk for office" 2>/dev/null
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_metadesc "Good quality standing desk. Very nice desk for working. Buy it now at our store today." 2>/dev/null
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_focuskw "" 2>/dev/null
            echo "  ✓ Standing Desk - Added poor SEO (repetitive, no keywords)"
            ;;
        *"Echo"*|*"Hub"*|*"Smart"*|*"Home"*)
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_title "Smart Home Hub" 2>/dev/null
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_metadesc "" 2>/dev/null
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_focuskw "smart home" 2>/dev/null
            echo "  ✓ Smart Hub - Added poor SEO (empty meta description)"
            ;;
        *"Keyboard"*|*"Logitech"*|*"Mechanical"*)
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_title "Logitech MX Mechanical Wireless Keyboard with Tactile Switches and Backlight for Professional Use and Gaming" 2>/dev/null
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_metadesc "mechanical keyboard wireless" 2>/dev/null
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_focuskw "keyboard" 2>/dev/null
            echo "  ✓ Keyboard - Added poor SEO (title way too long, description too short)"
            ;;
        *"Webcam"*|*"Brio"*|*"4K"*|*"Camera"*)
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_title "Webcam" 2>/dev/null
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_metadesc "4K webcam for video calls and streaming with HDR" 2>/dev/null
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_focuskw "webcam, 4k camera" 2>/dev/null
            echo "  ✓ Webcam - Added poor SEO (title too generic, missing benefits)"
            ;;
        *"Samsung"*|*"SSD"*|*"T7"*|*"Storage"*)
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_title "Samsung T7 SSD" 2>/dev/null
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_metadesc "Fast portable SSD hard drive external storage 2TB" 2>/dev/null
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_focuskw "" 2>/dev/null
            echo "  ✓ Samsung SSD - Added poor SEO (keyword stuffing, no keywords set)"
            ;;
        *"Coffee"*|*"Breville"*|*"Maker"*)
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_title "Coffee Maker" 2>/dev/null
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_metadesc "Make coffee at home with this coffee machine" 2>/dev/null
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_focuskw "coffee, coffee maker" 2>/dev/null
            echo "  ✓ Coffee Maker - Added poor SEO (too generic, weak value prop)"
            ;;
        *"Belkin"*|*"Charging"*|*"Wireless"*|*"MagSafe"*)
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_title "Belkin 3-in-1 Wireless Charging Stand for Apple iPhone Watch AirPods MagSafe Compatible Premium Design Black" 2>/dev/null
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_metadesc "wireless charger" 2>/dev/null
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_focuskw "wireless charger, magsafe" 2>/dev/null
            echo "  ✓ Wireless Charger - Added poor SEO (title too long, description too short)"
            ;;
        *)
            # Generic bad SEO for any other products
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_title "$(echo $TITLE | cut -c1-30)" 2>/dev/null
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_metadesc "Buy now" 2>/dev/null
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_focuskw "" 2>/dev/null
            echo "  ✓ $TITLE - Added poor SEO"
            ;;
    esac
done

echo ""
# ============================================
# PAGES - Add intentionally bad SEO meta
# ============================================
echo "📄 Processing Pages..."
docker compose run --rm wpcli wp post list --post_type=page --format=csv --fields=ID,post_title 2>/dev/null | tail -n +2 | while IFS=, read -r ID TITLE; do
    
    case "$TITLE" in
        *"About"*)
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_title "About" 2>/dev/null
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_metadesc "Learn more about us and our company history and mission statement and values" 2>/dev/null
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_focuskw "" 2>/dev/null
            echo "  ✓ About - Added poor SEO (title too short, wordy description)"
            ;;
        *"Contact"*)
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_title "Contact Us | Get In Touch With Our Team | We'd Love To Hear From You" 2>/dev/null
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_metadesc "Contact us" 2>/dev/null
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_focuskw "contact" 2>/dev/null
            echo "  ✓ Contact - Added poor SEO (title too long, description too short)"
            ;;
        *"Service"*)
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_title "Our Services" 2>/dev/null
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_metadesc "" 2>/dev/null
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_focuskw "services" 2>/dev/null
            echo "  ✓ Services - Added poor SEO (empty meta description)"
            ;;
        *"Privacy"*)
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_title "Privacy Policy" 2>/dev/null
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_metadesc "Read our privacy policy page to learn about how we handle your data" 2>/dev/null
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_focuskw "" 2>/dev/null
            echo "  ✓ Privacy Policy - Added poor SEO (boring, no keywords)"
            ;;
        *"Terms"*)
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_title "Terms" 2>/dev/null
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_metadesc "Terms and conditions" 2>/dev/null
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_focuskw "" 2>/dev/null
            echo "  ✓ Terms - Added poor SEO (too generic)"
            ;;
        *"FAQ"*)
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_title "FAQ - Frequently Asked Questions Page" 2>/dev/null
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_metadesc "Questions" 2>/dev/null
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_focuskw "faq" 2>/dev/null
            echo "  ✓ FAQ - Added poor SEO (redundant title, vague description)"
            ;;
        *"Pricing"*|*"Price"*)
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_title "Pricing" 2>/dev/null
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_metadesc "View our pricing and plans" 2>/dev/null
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_focuskw "" 2>/dev/null
            echo "  ✓ Pricing - Added poor SEO (no value proposition)"
            ;;
        *"Team"*)
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_title "Meet Our Amazing Team Of Talented Professionals" 2>/dev/null
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_metadesc "Team page" 2>/dev/null
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_focuskw "team, staff" 2>/dev/null
            echo "  ✓ Team - Added poor SEO (title too long, description useless)"
            ;;
        *"Career"*|*"Job"*)
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_title "Careers" 2>/dev/null
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_metadesc "Work with us. Join our team. We are hiring." 2>/dev/null
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_focuskw "" 2>/dev/null
            echo "  ✓ Careers - Added poor SEO (choppy sentences)"
            ;;
        *"Support"*|*"Help"*)
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_title "Support" 2>/dev/null
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_metadesc "" 2>/dev/null
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_focuskw "support, help" 2>/dev/null
            echo "  ✓ Support - Added poor SEO (empty description)"
            ;;
        *)
            # Generic bad SEO for any other pages
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_title "$TITLE" 2>/dev/null
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_metadesc "Learn more about $TITLE on our website." 2>/dev/null
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_focuskw "" 2>/dev/null
            echo "  ✓ $TITLE - Added poor SEO"
            ;;
    esac
done

echo ""
# ============================================
# POSTS (Blog Content) - Add intentionally bad SEO meta
# ============================================
echo "📝 Processing Blog Posts..."
docker compose run --rm wpcli wp post list --post_type=post --format=csv --fields=ID,post_title 2>/dev/null | tail -n +2 | while IFS=, read -r ID TITLE; do
    
    case "$TITLE" in
        *"Remote Work"*|*"Future"*|*"2025"*)
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_title "Remote Work" 2>/dev/null
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_metadesc "This article talks about remote work and the future" 2>/dev/null
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_focuskw "remote work" 2>/dev/null
            echo "  ✓ Remote Work - Added poor SEO (generic, no compelling hook)"
            ;;
        *"SEO"*|*"Best Practices"*)
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_title "10 Best Practices for SEO in 2024 - Ultimate Guide" 2>/dev/null
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_metadesc "SEO tips" 2>/dev/null
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_focuskw "" 2>/dev/null
            echo "  ✓ SEO Best Practices - Added poor SEO (description too short, no keywords)"
            ;;
        *"AI"*|*"Content Marketing"*|*"Transform"*)
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_title "AI Content Marketing" 2>/dev/null
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_metadesc "Artificial intelligence is revolutionizing how we create distribute and optimize content from automated generation to predictive analytics" 2>/dev/null
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_focuskw "ai, content marketing" 2>/dev/null
            echo "  ✓ AI Content Marketing - Added poor SEO (description too long, no punctuation)"
            ;;
        *"WordPress Security"*|*"Security"*)
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_title "WordPress Security Tips" 2>/dev/null
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_metadesc "" 2>/dev/null
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_focuskw "wordpress, security" 2>/dev/null
            echo "  ✓ WordPress Security - Added poor SEO (empty description)"
            ;;
        *"E-commerce"*|*"Online Business"*|*"Building"*)
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_title "Building a Successful E-commerce Business Online: A Complete Guide for Entrepreneurs" 2>/dev/null
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_metadesc "How to build an online store" 2>/dev/null
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_focuskw "" 2>/dev/null
            echo "  ✓ E-commerce - Added poor SEO (title too long, description too vague)"
            ;;
        *"Content Marketing Strategy"*)
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_title "Content Marketing" 2>/dev/null
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_metadesc "Content marketing strategies for small businesses and startups to grow" 2>/dev/null
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_focuskw "content marketing" 2>/dev/null
            echo "  ✓ Content Marketing - Added poor SEO (title too short, no hook)"
            ;;
        *"Social Media"*|*"Trends"*)
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_title "Social Media Trends" 2>/dev/null
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_metadesc "Read this blog post about social media trends" 2>/dev/null
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_focuskw "" 2>/dev/null
            echo "  ✓ Social Media - Added poor SEO (self-referential description)"
            ;;
        *"Email Marketing"*)
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_title "Email Marketing Best Practices Guide" 2>/dev/null
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_metadesc "Email" 2>/dev/null
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_focuskw "email marketing, email" 2>/dev/null
            echo "  ✓ Email Marketing - Added poor SEO (description way too short)"
            ;;
        *"Web Design"*|*"UX"*|*"User Experience"*)
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_title "Web Design Principles for Better User Experience and Improved Conversion Rates" 2>/dev/null
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_metadesc "Design principles" 2>/dev/null
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_focuskw "" 2>/dev/null
            echo "  ✓ Web Design - Added poor SEO (title too long, description lacks detail)"
            ;;
        *"Analytics"*|*"Metrics"*|*"Digital Marketing"*)
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_title "Analytics" 2>/dev/null
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_metadesc "Learn about digital marketing analytics and the metrics that matter most for your business success online" 2>/dev/null
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_focuskw "analytics, metrics" 2>/dev/null
            echo "  ✓ Analytics - Added poor SEO (title too short, description too wordy)"
            ;;
        *)
            # Generic bad SEO for any other posts
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_title "$(echo $TITLE | cut -c1-40)" 2>/dev/null
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_metadesc "Read this article" 2>/dev/null
            docker compose run --rm wpcli wp post meta update $ID _yoast_wpseo_focuskw "" 2>/dev/null
            echo "  ✓ $TITLE - Added poor SEO"
            ;;
    esac
done

echo ""
echo "============================================"
echo "✅ Test SEO data setup complete!"
echo "============================================"
echo ""
echo "📊 Summary:"
docker compose run --rm wpcli wp post list --post_type=product --format=count 2>/dev/null | xargs echo "   Products:"
docker compose run --rm wpcli wp post list --post_type=page --format=count 2>/dev/null | xargs echo "   Pages:"
docker compose run --rm wpcli wp post list --post_type=post --format=count 2>/dev/null | xargs echo "   Posts:"
echo ""
echo "🧪 SEO Problems Created:"
echo "   ❌ Titles too short (missing keywords & context)"
echo "   ❌ Titles too long (>60 characters, will be cut off)"
echo "   ❌ Meta descriptions missing (empty strings)"
echo "   ❌ Meta descriptions too short (<50 chars)"
echo "   ❌ Meta descriptions too long (>160 chars)"
echo "   ❌ Generic, boring copy with no value proposition"
echo "   ❌ Missing or poor keyword targeting"
echo "   ❌ Keyword stuffing in descriptions"
echo "   ❌ No compelling calls-to-action"
echo "   ❌ Self-referential content ('Read this article')"
echo ""
echo "✨ Now test your plugin to improve these!"
echo "   🔗 Visit: http://localhost:8080/wp-admin/admin.php?page=seo-optimizer-analysis"
echo ""
echo "💡 Test Scenarios:"
echo "   1. Switch between Product/Content/Pages tabs"
echo "   2. Select items with different SEO problems"
echo "   3. Try different Writing Style & Tone inputs"
echo "   4. Generate AI recommendations"
echo "   5. Apply and verify improvements"
echo ""
