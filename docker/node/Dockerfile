FROM node:22-alpine

# npm 10 (bundled with node 22) crashes resolving this dependency tree; npm 11 is fine.
RUN npm install -g npm@11

WORKDIR /var/www/frontend

EXPOSE 5173

CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0"]
