package generator

import (
	"context"
	"github.com/getkin/kin-openapi/openapi3"
)

type MediaGenerator interface {
	GenerateData(ctx context.Context, mediaType *openapi3.MediaType) (Data, error)
}

func New(options Options) MediaGenerator {
	generatorsByType := map[string]schemaGenerator{
		"object": &objectGenerator{},
		"string": &stringGenerator{},
	}

	var schemaGenerator schemaGenerator

	schemaGenerator = &coordinatingSchemaGenerator{
		generatorsByType: generatorsByType,
	}

	if options.UseExamples != No {
		schemaGenerator = &exampleSchemaGenerator{
			useExamples:     options.UseExamples,
			schemaGenerator: schemaGenerator,
		}
	}

	for i := range generatorsByType {
		if generator, ok := generatorsByType[i].(recursiveGenerator); ok == true {
			generator.SetSchemaGenerator(schemaGenerator)
		}
	}

	return &coordinatingMediaGenerator{
		useExamples:     options.UseExamples,
		schemaGenerator: schemaGenerator,
	}
}
