#!/bin/bash

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}🚀 Starting deployment process...${NC}\n"

# Check if we're in a git repository
if [ ! -d .git ]; then
    echo -e "${RED}❌ Error: Not a git repository${NC}"
    exit 1
fi

# Check for uncommitted changes
if [[ -n $(git status -s) ]]; then
    echo -e "${RED}❌ Error: You have uncommitted changes${NC}"
    echo "Please commit or stash your changes first"
    git status -s
    exit 1
fi

# Get current branch
BRANCH=$(git branch --show-current)
echo -e "${GREEN}📌 Current branch: ${BRANCH}${NC}"

# Push to GitHub
echo -e "${YELLOW}📤 Pushing to GitHub...${NC}"
git push origin $BRANCH

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Successfully pushed to GitHub${NC}"
    echo -e "${GREEN}✅ GitHub Actions will handle deployment${NC}"
    echo -e "\n${YELLOW}View deployment status at:${NC}"
    echo "https://github.com/YOUR_USERNAME/seo-wp-plugin/actions"
else
    echo -e "${RED}❌ Failed to push to GitHub${NC}"
    exit 1
fi
