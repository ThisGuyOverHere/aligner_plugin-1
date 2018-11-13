import React, {Component} from 'react';
import PropTypes from "prop-types";

class SearchComponent extends Component {
    static propTypes = {
        job: PropTypes.shape({
            config: PropTypes.shape({
                password: PropTypes.any,
                id: PropTypes.any
            }),
            rows: PropTypes.array,
            rowsDictionary: PropTypes.any
        }),
    };

    constructor(props) {
        super(props);
        this.state = {
            elements: [],
            searchResults: [],
            featuredSearchResult: null,
            active: false,
            searchTxt: ''
        };
    }


    componentDidMount() {
    }

    componentWillUnmount() {

    }

    static getDerivedStateFromProps = (props, state) => {
        let elements = [];
        props.job.rows.map((row, index) => {
            const source = {
                content: row.source.content_clean.toLowerCase(),
                type: 'source',
                id: row.source.id,
                index: index,
            };
            const target = {
                content: row.target.content_clean.toLowerCase(),
                type: 'target',
                id: row.target.id,
                index: index,
            };
            elements.push(source, target);
        });

        state.elements = elements;
        return state;
    };

    render() {
        return (
            <div id="search">
                <form onSubmit={this.onPerformSearch}>
                    <input type="text" value={this.state.searchTxt} onChange={this.onSearchChange}/>
                </form>
            </div>
        );
    }

    onPerformSearch = (e) => {
        e.preventDefault();
        let fulltext = this.state.searchTxt.toLowerCase();

        const result = this.state.elements.filter(function (item) {
            return item.content.indexOf(fulltext) !== -1;
        });

        console.log(result);
        this.setState({
            active: true,
            searchResults: result,
            featuredSearchResult: 0
        });
    };
    onSearchChange = (event) => {
        this.setState({
            searchTxt: event.target.value
        })
    }
}

export default SearchComponent;
