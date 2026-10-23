# Production web image: builds the SPA and serves it with nginx. Build context: repository root.
FROM node:22-alpine AS build
# npm 10 (bundled with node 22) crashes on this dependency tree; npm 11 is fine.
RUN npm install -g npm@11
WORKDIR /app
COPY frontend/package.json frontend/package-lock.json ./
RUN npm ci
COPY frontend/ ./
RUN npm run build

FROM nginx:1.27-alpine
COPY docker/nginx/prod.conf /etc/nginx/conf.d/default.conf
COPY --from=build /app/dist /usr/share/nginx/html
EXPOSE 80
