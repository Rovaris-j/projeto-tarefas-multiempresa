# 1. Imagem base com Node estável
FROM node:18

# 2. Pasta de trabalho
WORKDIR /app

# 3. Copia os arquivos de dependência
COPY package*.json ./

# 4. Instala dependências
RUN npm install

# 5. Copia o restante do projeto
COPY . .

# 6. Porta usada pelo Vite / Vue
EXPOSE 5173

CMD ["npm", "run", "dev", "--", "--host"]