#ifndef EDGEDATATYPE_CU
#define EDGEDATATYPE_CU

#include "../Algorithm/EdgeDataType.h"
#include "../MultipleGPU/MultiGraphStorage.h"
#include "../MedusaRT/Utilities.h"
#include "Configuration.h"



EdgeArray::EdgeArray()
{
	srcVertexID = NULL;
	dstVertexID = NULL;
#ifdef MESSAGING
	msgDstID = NULL;
	incoming_msg_flag = NULL;
#endif
	edgeOffset = NULL;
	level_count = 0;
	size = 0;
}

void EdgeArray::resize(int num)
{
	if(size != 0)
	{
		free(srcVertexID);
		free(dstVertexID);
#ifdef MESSAGING
		free(msgDstID);
		free(incoming_msg_flag);
#endif
	}
	size = num;
	CPUMalloc((void**)&srcVertexID,sizeof(int)*num);
	CPUMalloc((void**)&dstVertexID,sizeof(int)*num);
#ifdef MESSAGING
	CPUMalloc((void**)&msgDstID,sizeof(int)*num);
	CPUMalloc((void**)&incoming_msg_flag,sizeof(unsigned int)*num);
#endif
}

void EdgeArray::assign(int i, Edge e)
{
	srcVertexID[i] = e.srcVertexID;
	dstVertexID[i] = e.dstVertexID;
	//	msgDstID[i] = e.msgDstID;
}

void EdgeArray::buildMEG(GraphIR &graph)
{

#ifndef MEG
	//caution
	printf("The configuration doesn't indicate using MEG\n");
#else
	/* check if the graph is sorted in descending order*/
	for(int i = 0; i < graph.vertexNum - 1; i ++)
		if(graph.vertexArray[i].vertex.edge_count < graph.vertexArray[i+1].vertex.edge_count)
		{
			printf("Before convert to MEG, the GraphIR must be sorted\n");
			exit(-1);
		}


		/* count the number of edges in each level */
		level_count = 0;
		for(int i = 0; i <  graph.vertexNum; i ++)
			if(graph.vertexArray[i].vertex.edge_count > level_count)
				level_count = graph.vertexArray[i].vertex.edge_count;
		CPUMalloc((void**)&edgeOffset,sizeof(int)*level_count);
		memset(edgeOffset, 0, sizeof(int)*level_count);
		for(int i = 0; i < level_count; i ++)
		{
			for(int j = 0; j <  graph.vertexNum; j ++)
			{
				if(graph.vertexArray[j].vertex.edge_count > i)
					edgeOffset[i] ++;
			}
		}

		/* construct MEG from GraphIR */
		// construct edge array

		resize(graph.totalEdgeCount);
		int placeIndex;
		int levelIndex;
		for(int i = 0; i < graph.vertexNum; i ++)
		{
			placeIndex = i;
			levelIndex = 0;
			// loop through the edge list
			if(graph.vertexArray[i].vertex.edge_count)
			{
				EdgeNode *tempEdgeNode = graph.vertexArray[i].firstEdge;
				while(tempEdgeNode != NULL)
				{
					assign(placeIndex, tempEdgeNode->edge);
					tempEdgeNode = tempEdgeNode->nextEdge;
					placeIndex += edgeOffset[levelIndex ++];
				}
			}
		}

#ifdef MESSAGING

		//compute incoming message flag for the combiner
		int *vertex_edge_count = (int*)malloc(sizeof(int)*graph.vertexNum);
		for(int i = 0; i < graph.vertexNum; i ++)
			vertex_edge_count[i] = graph.vertexArray[i].incoming_edge_count;



		//compute prefix sum
		int last_edge_count = vertex_edge_count[0];
		vertex_edge_count[0] = 0;
		for(int i = 1; i < graph.vertexNum; i ++)
		{
			int temp_edge_count = vertex_edge_count[i];
			vertex_edge_count[i] = vertex_edge_count[i - 1] + last_edge_count;
			last_edge_count = temp_edge_count; 
		}

		memset(incoming_msg_flag, 0, sizeof(unsigned int)*size);
		for(int i = 0; i < graph.vertexNum; i ++)
		{
			incoming_msg_flag[vertex_edge_count[i]] = 1;
			//	printf("set %d to 1\n",vertex_edge_count[i]);
		}
		//compute reverse edge ID
		for(int i = 0; i < size; i ++)
			msgDstID[i] = vertex_edge_count[dstVertexID[i]] ++;

		free(vertex_edge_count);
#endif
#endif
}

void EdgeArray::buildAA(GraphIR &graph)
{
	/* construct MEG from GraphIR */
	// construct edge array
	//��ΪAAҪ����CPU�����ݽṹ����������Ҫ������������ʾ
#ifndef AA
	printf("The configuration doesn't indicate using AA\n");
	//exit(-1);
#endif
	resize(graph.totalEdgeCount);
	int placeIndex = 0;
	for(int i = 0; i < graph.vertexNum; i ++)
	{
		EdgeNode *tempEdgeNode = graph.vertexArray[i].firstEdge;
		
		while(tempEdgeNode != NULL)
		{
			
			//printf("%d - > %d\n",tempEdgeNode->edge.srcVertexID, tempEdgeNode->edge.dstVertexID);
			if(tempEdgeNode->edge.srcVertexID != i)
			{
				printf("edge src %d doesnot mat vertex ID %d\n",tempEdgeNode->edge.srcVertexID, i);
				exit(-1);
			}
			assign(placeIndex, tempEdgeNode->edge);
			tempEdgeNode = tempEdgeNode->nextEdge;
			placeIndex ++;
		}
	}
#ifdef MESSAGING	
	//compute incoming message flag for the combiner
	int *vertex_edge_count = (int*)malloc(sizeof(int)*graph.vertexNum);
	for(int i = 0; i < graph.vertexNum; i ++)
		vertex_edge_count[i] = graph.vertexArray[i].incoming_edge_count;



	//compute prefix sum
	int last_edge_count = vertex_edge_count[0];
	vertex_edge_count[0] = 0;
	for(int i = 1; i < graph.vertexNum; i ++)
	{
		int temp_edge_count = vertex_edge_count[i];
		vertex_edge_count[i] = vertex_edge_count[i - 1] + last_edge_count;
		last_edge_count = temp_edge_count; 
	}


	memset(incoming_msg_flag, 0, sizeof(unsigned int)*size);
	for(int i = 0; i < graph.vertexNum; i ++)
		incoming_msg_flag[vertex_edge_count[i]] = 1;
	//compute reverse edge ID
	for(int i = 0; i < size; i ++)
		msgDstID[i] = vertex_edge_count[dstVertexID[i]] ++;

	free(vertex_edge_count);
#endif


}






void EdgeArray::buildELL(GraphIR &graph)
{
	//find the maximum degree
#ifndef ELL
	//caution
	printf("The configuration doesn't indicate using ELL\n");

#else
	int max_degree = 0;
	for(int i = 0; i < graph.vertexNum; i ++)
		if(graph.vertexArray[i].vertex.edge_count > max_degree)
			max_degree = graph.vertexArray[i].vertex.edge_count;
	printf("ELL edge memory space %d\n", graph.vertexNum*max_degree); 
	resize(graph.vertexNum*max_degree);
	for(int i = 0; i < graph.vertexNum; i ++)
	{
		int placeIndex = i;
		EdgeNode *tempEdgeNode = graph.vertexArray[i].firstEdge;
		while(tempEdgeNode != NULL)
		{
			if(placeIndex > graph.vertexNum*max_degree)
				printf("placeIndex error\n");
			assign(placeIndex, tempEdgeNode->edge);
			tempEdgeNode = tempEdgeNode->nextEdge;
			placeIndex += graph.vertexNum;
		}
	}
#endif

}

/**
* The first part of the edge_array is ELL, the second part is AA
*
* @param   - 
* @return	
* @note	
*
*/
void EdgeArray::buildHY(GraphIR &graph, VertexArray &varr, int threshold)
{
	//find the length of AA

#ifndef HY
	//caution
	printf("The configuration doesn't indicate using HY\n");
#else

	int AA_length = graph.vertexNum*threshold;
	for(int i = 0; i < graph.vertexNum; i ++)
	{
		varr.edge_index[i] = AA_length;
		if(graph.vertexArray[i].vertex.edge_count > threshold)
		{
			AA_length += (graph.vertexArray[i].vertex.edge_count - threshold);
			varr.edge_count[i] = threshold;
		}
		else
			varr.edge_count[i] = graph.vertexArray[i].vertex.edge_count;

	}
	varr.edge_index[graph.vertexNum] = AA_length;
	printf("HY edge memory space %d index space %d\n", AA_length, graph.vertexNum);
	resize(AA_length);
	//	printf("AA_length + graph.vertexNum*threshold = %d",AA_length + graph.vertexNum*threshold);
	int ELL_count;
	int placeIndex;
	int AA_index = graph.vertexNum*threshold;
	for(int i = 0; i < graph.vertexNum; i ++)
	{
		placeIndex = i;
		ELL_count = 0;
		EdgeNode *tempEdgeNode = graph.vertexArray[i].firstEdge;
		while(tempEdgeNode != NULL)
		{
			if(ELL_count < threshold)
			{
				if(placeIndex >= AA_length + graph.vertexNum*threshold)
					printf("placeIndex error\n");
				assign(placeIndex, tempEdgeNode->edge);
				ELL_count ++;

			}
			else
				assign(AA_index ++, tempEdgeNode->edge);

			tempEdgeNode = tempEdgeNode->nextEdge;
			placeIndex += graph.vertexNum;
		}
	}
#endif	

}

















void D_EdgeArray::Fill(EdgeArray &ea)
{
	if(size != 0)
	{
		CUDA_SAFE_CALL(cudaFree(d_srcVertexID));
		CUDA_SAFE_CALL(cudaFree(d_dstVertexID));
#ifdef MESSAGING
		CUDA_SAFE_CALL(cudaFree(d_msgDstID));
		CUDA_SAFE_CALL(cudaFree(d_incoming_msg_flag));
#endif
#ifdef MEG
		CUDA_SAFE_CALL(cudaFree(d_edgeOffset));
#endif
	}
	size = ea.size;
	GPUMalloc((void**)&d_srcVertexID,sizeof(int)*size);
	GPUMalloc((void**)&d_dstVertexID,sizeof(int)*size);
#ifdef MEG
	GPUMalloc((void**)&d_edgeOffset, sizeof(int)*ea.level_count);
	CUDA_SAFE_CALL(cudaMemcpy(d_edgeOffset, ea.edgeOffset, sizeof(int)*ea.level_count, cudaMemcpyHostToDevice));
#endif

	//	GPUMalloc((void**)&d_msgDstID,sizeof(int)*size);

	CUDA_SAFE_CALL(cudaMemcpy(d_srcVertexID, ea.srcVertexID, sizeof(int)*size, cudaMemcpyHostToDevice));
	CUDA_SAFE_CALL(cudaMemcpy(d_dstVertexID, ea.dstVertexID, sizeof(int)*size, cudaMemcpyHostToDevice));
#ifdef MESSAGING	
	GPUMalloc((void**)&d_incoming_msg_flag,sizeof(unsigned int)*size);
	CUDA_SAFE_CALL(cudaMemcpy(d_msgDstID, ea.msgDstID, sizeof(int)*size, cudaMemcpyHostToDevice));
	CUDA_SAFE_CALL(cudaMemcpy(d_incoming_msg_flag, ea.incoming_msg_flag, sizeof(unsigned int)*size, cudaMemcpyHostToDevice));
#endif

}

#endif
