# Review System Overview

## What is the Review System?
The review system enables verified buyers to leave detailed feedback on products. It supports:
- **Aspect-based ratings**: Value for Money, True to Description, Product Quality, Shipping
- **Text and images**: Rich review content
- **Verified purchase**: Only users who purchased and received the product can review
- **Replies**: Threaded replies to reviews
- **Flagging**: Users can flag reviews for moderation
- **Helpful votes**: Users can upvote/downvote reviews as helpful
- **Moderation**: Admins can delete any review; users can delete their own

## Key Features
- One review per user per product
- Review summary and aspect breakdowns in product API
- Overall product rating (Fiverr-style average)
- Soft deletes for moderation

## Permissions
- Only verified buyers can review
- Only review authors or admins can delete reviews
- Any user can reply, flag, or vote helpful

## Where to Find More
- [API Reference](./api.md)
- [Frontend Integration Guide](./frontend.md) 