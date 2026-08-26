FROM node:22-alpine AS dependencias
WORKDIR /aplicacao
COPY package.json package-lock.json ./
RUN npm ci

FROM dependencias AS desenvolvimento
COPY . .
EXPOSE 5173
CMD ["npm", "run", "desenvolver"]

FROM dependencias AS construcao
COPY . .
RUN npm run build

FROM nginx:1.27-alpine AS producao
COPY docker/nginx.conf /etc/nginx/conf.d/default.conf
COPY --from=construcao /aplicacao/dist /usr/share/nginx/html
EXPOSE 80
