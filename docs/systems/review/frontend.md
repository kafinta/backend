# Review System Frontend Integration Guide

## How to Use This Guide
This guide provides practical notes for integrating the review system into your frontend application. For each operation, you’ll find:
- **Endpoint path and method**
- **Purpose/description**
- **Required/optional fields**
- **Auth requirements**
- **Workflow notes**
- **Error handling and common pitfalls**

---

## Displaying Reviews
- Show overall product rating (headline, e.g., 4.8 stars)
- Show aspect breakdowns (Value for Money, etc.)
- Show review count and latest reviews
- Display each review’s text, images, aspect ratings, overall_rating, verified badge, helpful_votes
- Show replies (threaded)
- Show flag status if flagged

## Submitting a Review
- Only show review form if user is eligible (purchased & received)
- Require all aspect ratings (1-5)
- Allow optional comment and images
- Show error if already reviewed or not eligible

## Updating/Deleting a Review
- Only review author can update/delete
- Show edit/delete buttons for own reviews
- Update review in place on success

## Replying to a Review
- Any user can reply
- Show reply form under each review
- Replies do not have aspect ratings

## Flagging a Review
- Any user can flag
- Show flag button with reason input
- Show flagged status if applicable

## Helpful Voting
- Any user can upvote/downvote a review
- Show current helpful_votes count
- Prevent duplicate votes; allow changing vote
- Show feedback on vote (e.g., “Vote recorded”)

## Moderation
- Admins can delete any review
- Show moderation actions only for admins

---

## Common Pitfalls
- Don’t allow multiple reviews per user per product
- Don’t show review form to ineligible users
- Handle error states (403, 409, 422) gracefully
- Always show aspect breakdowns and overall rating for transparency 