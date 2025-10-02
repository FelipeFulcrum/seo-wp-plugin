#!/bin/bash

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo "======================================"
echo "🧪 SEO Optimizer Quality Test Suite"
echo "======================================"
echo ""

# Initialize scores
total_tests=0
passed_tests=0
failed_tests=0

# Function to test a single piece of content
test_seo_quality() {
    local post_id=$1
    local post_title=$2
    local post_type=$3
    
    echo ""
    echo "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo "${BLUE}Testing: $post_title (ID: $post_id)${NC}"
    echo "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    
    # Get meta data
    seo_title=$(docker compose run --rm wpcli wp post meta get $post_id _yoast_wpseo_title 2>/dev/null | tr -d '\r')
    seo_desc=$(docker compose run --rm wpcli wp post meta get $post_id _yoast_wpseo_metadesc 2>/dev/null | tr -d '\r')
    seo_keywords=$(docker compose run --rm wpcli wp post meta get $post_id _yoast_wpseo_focuskw 2>/dev/null | tr -d '\r')
    
    local test_score=0
    local max_score=10
    
    # Test 1: Title Length (optimal 50-60 characters)
    title_length=${#seo_title}
    echo -n "  📏 Title Length ($title_length chars): "
    if [ $title_length -ge 50 ] && [ $title_length -le 60 ]; then
        echo -e "${GREEN}✓ PERFECT${NC} (50-60 chars)"
        ((test_score++))
        ((passed_tests++))
    elif [ $title_length -ge 40 ] && [ $title_length -le 70 ]; then
        echo -e "${YELLOW}⚠ ACCEPTABLE${NC} (40-70 chars)"
        ((test_score++))
        ((passed_tests++))
    elif [ $title_length -eq 0 ]; then
        echo -e "${RED}✗ FAIL - Empty title${NC}"
        ((failed_tests++))
    else
        echo -e "${RED}✗ FAIL${NC} (Too short or too long)"
        ((failed_tests++))
    fi
    ((total_tests++))
    
    # Test 2: Description Length (optimal 150-160 characters)
    desc_length=${#seo_desc}
    echo -n "  📏 Description Length ($desc_length chars): "
    if [ $desc_length -ge 150 ] && [ $desc_length -le 160 ]; then
        echo -e "${GREEN}✓ PERFECT${NC} (150-160 chars)"
        ((test_score++))
        ((passed_tests++))
    elif [ $desc_length -ge 120 ] && [ $desc_length -le 165 ]; then
        echo -e "${YELLOW}⚠ ACCEPTABLE${NC} (120-165 chars)"
        ((test_score++))
        ((passed_tests++))
    elif [ $desc_length -eq 0 ]; then
        echo -e "${RED}✗ FAIL - Empty description${NC}"
        ((failed_tests++))
    else
        echo -e "${RED}✗ FAIL${NC} (Too short or too long)"
        ((failed_tests++))
    fi
    ((total_tests++))
    
    # Test 3: Keywords exist
    echo -n "  🔑 Keywords Present: "
    if [ -n "$seo_keywords" ] && [ "$seo_keywords" != "" ]; then
        # Count keywords
        keyword_count=$(echo "$seo_keywords" | grep -o "," | wc -l)
        keyword_count=$((keyword_count + 1))
        echo -e "${GREEN}✓ PASS${NC} ($keyword_count keywords)"
        ((test_score++))
        ((passed_tests++))
    else
        echo -e "${RED}✗ FAIL - No keywords${NC}"
        ((failed_tests++))
    fi
    ((total_tests++))
    
    # Test 4: Title contains keywords
    echo -n "  🎯 Title Contains Keywords: "
    if [ -n "$seo_keywords" ]; then
        first_keyword=$(echo "$seo_keywords" | cut -d',' -f1 | xargs)
        if [[ "$seo_title" == *"$first_keyword"* ]] || [[ "$seo_title" == *"${first_keyword^}"* ]]; then
            echo -e "${GREEN}✓ PASS${NC}"
            ((test_score++))
            ((passed_tests++))
        else
            echo -e "${RED}✗ FAIL${NC}"
            ((failed_tests++))
        fi
    else
        echo -e "${YELLOW}⚠ SKIP - No keywords to check${NC}"
    fi
    ((total_tests++))
    
    # Test 5: Description contains keywords
    echo -n "  🎯 Description Contains Keywords: "
    if [ -n "$seo_keywords" ]; then
        first_keyword=$(echo "$seo_keywords" | cut -d',' -f1 | xargs)
        if [[ "$seo_desc" == *"$first_keyword"* ]] || [[ "$seo_desc" == *"${first_keyword^}"* ]]; then
            echo -e "${GREEN}✓ PASS${NC}"
            ((test_score++))
            ((passed_tests++))
        else
            echo -e "${RED}✗ FAIL${NC}"
            ((failed_tests++))
        fi
    else
        echo -e "${YELLOW}⚠ SKIP - No keywords to check${NC}"
    fi
    ((total_tests++))
    
    # Test 6: Title is not just keyword stuffing
    echo -n "  📝 Title Quality (not keyword stuffing): "
    word_count=$(echo "$seo_title" | wc -w)
    if [ $word_count -ge 5 ]; then
        echo -e "${GREEN}✓ PASS${NC} ($word_count words)"
        ((test_score++))
        ((passed_tests++))
    else
        echo -e "${RED}✗ FAIL - Too few words${NC}"
        ((failed_tests++))
    fi
    ((total_tests++))
    
    # Test 7: Description has punctuation (not just keywords)
    echo -n "  ✍️  Description Quality (proper sentences): "
    if [[ "$seo_desc" == *"."* ]] || [[ "$seo_desc" == *"!"* ]] || [[ "$seo_desc" == *"?"* ]]; then
        echo -e "${GREEN}✓ PASS${NC}"
        ((test_score++))
        ((passed_tests++))
    else
        echo -e "${YELLOW}⚠ WARNING - No punctuation${NC}"
        ((failed_tests++))
    fi
    ((total_tests++))
    
    # Test 8: No excessive capitalization
    echo -n "  🔤 No Excessive Caps: "
    caps_count=$(echo "$seo_title" | grep -o '[A-Z]' | wc -l)
    total_letters=$(echo "$seo_title" | grep -o '[a-zA-Z]' | wc -l)
    if [ $total_letters -gt 0 ]; then
        caps_ratio=$((caps_count * 100 / total_letters))
        if [ $caps_ratio -lt 30 ]; then
            echo -e "${GREEN}✓ PASS${NC} (${caps_ratio}% caps)"
            ((test_score++))
            ((passed_tests++))
        else
            echo -e "${RED}✗ FAIL - Too many caps${NC} (${caps_ratio}%)"
            ((failed_tests++))
        fi
    else
        echo -e "${YELLOW}⚠ SKIP${NC}"
    fi
    ((total_tests++))
    
    # Test 9: Title doesn't end with pipe or dash (brand should be added by theme)
    echo -n "  🚫 No trailing separators: "
    if [[ ! "$seo_title" =~ [\|\-]$ ]]; then
        echo -e "${GREEN}✓ PASS${NC}"
        ((test_score++))
        ((passed_tests++))
    else
        echo -e "${YELLOW}⚠ WARNING - Ends with separator${NC}"
        ((failed_tests++))
    fi
    ((total_tests++))
    
    # Test 10: Description doesn't start with filler words
    echo -n "  💡 No filler opening: "
    if [[ ! "$seo_desc" =~ ^(Learn|Read|Discover|Find out|Click here) ]]; then
        echo -e "${GREEN}✓ PASS${NC}"
        ((test_score++))
        ((passed_tests++))
    else
        echo -e "${YELLOW}⚠ WARNING - Generic opening${NC}"
        ((failed_tests++))
    fi
    ((total_tests++))
    
    # Calculate percentage score
    percentage=$((test_score * 100 / max_score))
    
    echo ""
    echo -n "  📊 Score: $test_score/$max_score ($percentage%) - "
    if [ $percentage -ge 90 ]; then
        echo -e "${GREEN}EXCELLENT ⭐⭐⭐⭐⭐${NC}"
    elif [ $percentage -ge 75 ]; then
        echo -e "${GREEN}GOOD ⭐⭐⭐⭐${NC}"
    elif [ $percentage -ge 60 ]; then
        echo -e "${YELLOW}ACCEPTABLE ⭐⭐⭐${NC}"
    elif [ $percentage -ge 40 ]; then
        echo -e "${YELLOW}NEEDS IMPROVEMENT ⭐⭐${NC}"
    else
        echo -e "${RED}POOR ⭐${NC}"
    fi
    
    echo ""
    echo "  📋 Current SEO Data:"
    echo "     Title: $seo_title"
    echo "     Description: $seo_desc"
    echo "     Keywords: $seo_keywords"
}

# Get all products
echo "🔍 Scanning WordPress content..."
echo ""

products=$(docker compose run --rm wpcli wp post list --post_type=product --format=csv --fields=ID,post_title 2>/dev/null | tail -n +2)
pages=$(docker compose run --rm wpcli wp post list --post_type=page --format=csv --fields=ID,post_title 2>/dev/null | tail -n +2)
posts=$(docker compose run --rm wpcli wp post list --post_type=post --format=csv --fields=ID,post_title 2>/dev/null | tail -n +2)

# Test products
if [ -n "$products" ]; then
    echo "${BLUE}═══════════════════════════════════════${NC}"
    echo "${BLUE}📦 TESTING PRODUCTS${NC}"
    echo "${BLUE}═══════════════════════════════════════${NC}"
    echo "$products" | while IFS=, read -r ID TITLE; do
        test_seo_quality "$ID" "$TITLE" "product"
    done
fi

# Test pages
if [ -n "$pages" ]; then
    echo "${BLUE}═══════════════════════════════════════${NC}"
    echo "${BLUE}📄 TESTING PAGES${NC}"
    echo "${BLUE}═══════════════════════════════════════${NC}"
    echo "$pages" | while IFS=, read -r ID TITLE; do
        test_seo_quality "$ID" "$TITLE" "page"
    done
fi

# Test posts
if [ -n "$posts" ]; then
    echo "${BLUE}═══════════════════════════════════════${NC}"
    echo "${BLUE}📝 TESTING BLOG POSTS${NC}"
    echo "${BLUE}═══════════════════════════════════════${NC}"
    echo "$posts" | while IFS=, read -r ID TITLE; do
        test_seo_quality "$ID" "$TITLE" "post"
    done
fi

# Final summary
echo ""
echo "======================================"
echo "📊 FINAL TEST RESULTS"
echo "======================================"
echo ""
echo "Total Tests: $total_tests"
echo -e "Passed: ${GREEN}$passed_tests${NC}"
echo -e "Failed: ${RED}$failed_tests${NC}"

if [ $total_tests -gt 0 ]; then
    pass_percentage=$((passed_tests * 100 / total_tests))
    echo ""
    echo -n "Overall Pass Rate: $pass_percentage% - "
    if [ $pass_percentage -ge 90 ]; then
        echo -e "${GREEN}EXCELLENT! 🎉${NC}"
    elif [ $pass_percentage -ge 75 ]; then
        echo -e "${GREEN}GOOD! ✅${NC}"
    elif [ $pass_percentage -ge 50 ]; then
        echo -e "${YELLOW}NEEDS IMPROVEMENT ⚠️${NC}"
    else
        echo -e "${RED}POOR - Needs Major Work 🔧${NC}"
    fi
fi

echo ""
echo "======================================"
