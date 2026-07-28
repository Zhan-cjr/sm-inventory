FROM node:20-alpine AS builder

WORKDIR /app

COPY package*.json ./

RUN npm config set fetch-retries 5 && npm config set fetch-retry-maxtimeout 120000 && npm install

COPY . .

RUN npm run build

# Stage 2: Serve static files via Nginx
FROM nginx:alpine

COPY --from=builder /app/dist /usr/share/nginx/html
COPY nginx-static.conf /etc/nginx/conf.d/default.conf

EXPOSE 8081

CMD ["nginx", "-g", "daemon off;"]
