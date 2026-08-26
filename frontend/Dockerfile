FROM node:22-alpine AS desenvolvimento
WORKDIR /aplicacao
COPY package.json ./
RUN npm install
COPY . .
EXPOSE 5173
CMD ["npm", "run", "desenvolver"]

